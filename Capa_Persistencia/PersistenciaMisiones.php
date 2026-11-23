<?php
require_once('IPersistenciaMisiones.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/* Progreso de misiones, experiencia y logros (Fase 2). Las definiciones viven en datos/mundo/misiones.json. */
class PersistenciaMisiones implements IPersistenciaMisiones
{
    private ?PDO $conn = null;
    private static ?PersistenciaMisiones $instancia = null;

    public static function getInstancia(): PersistenciaMisiones
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar el singletonMisiones");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaMisiones: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('Las misiones no están disponibles por un problema de conexión.');
        }
        return $this->conn;
    }

    public function iniciarTransaccion(): void { $this->db()->beginTransaction(); }
    public function confirmar(): void { if ($this->db()->inTransaction()) { $this->db()->commit(); } }
    public function revertir(): void { if ($this->db()->inTransaction()) { $this->db()->rollBack(); } }

    /** [IDMision => ['estado', 'paso', 'fechaPaso']] del jugador. */
    public function misionesDe(int $idUsuario): array
    {
        $s = $this->db()->prepare('SELECT IDMision, Estado, Paso, FechaPaso FROM MISIONES_JUGADOR WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        $r = [];
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $r[$f['IDMision']] = ['estado' => $f['Estado'], 'paso' => (int) $f['Paso'], 'fechaPaso' => $f['FechaPaso']];
        }
        return $r;
    }

    /** false si ya la tenía (no se puede aceptar dos veces). */
    public function iniciar(int $idUsuario, string $idMision): bool
    {
        $s = $this->db()->prepare('INSERT IGNORE INTO MISIONES_JUGADOR (IDUsuario, IDMision) VALUES (:u, :m)');
        $s->execute([':u' => $idUsuario, ':m' => $idMision]);
        return $s->rowCount() === 1;
    }

    public function avanzar(int $idUsuario, string $idMision, int $paso): void
    {
        $s = $this->db()->prepare("UPDATE MISIONES_JUGADOR SET Paso = :p, FechaPaso = NOW() WHERE IDUsuario = :u AND IDMision = :m AND Estado = 'activa'");
        $s->execute([':p' => $paso, ':u' => $idUsuario, ':m' => $idMision]);
    }

    /** false si ya estaba completada (evita cobrar la recompensa dos veces). */
    public function completar(int $idUsuario, string $idMision): bool
    {
        $s = $this->db()->prepare("UPDATE MISIONES_JUGADOR SET Estado = 'completada', FechaFin = NOW() WHERE IDUsuario = :u AND IDMision = :m AND Estado = 'activa'");
        $s->execute([':u' => $idUsuario, ':m' => $idMision]);
        return $s->rowCount() === 1;
    }

    /** Partidas terminadas (sin abandonar) desde $desde; opcionalmente solo ganadas o solo en cafés de un lado del Muro. */
    public function partidasDesde(int $idUsuario, string $desde, bool $ganadas, ?string $lado): int
    {
        $sql = "SELECT COUNT(*) FROM PARTICIPACIONES pa INNER JOIN PARTIDAS p ON p.IDPartida = pa.IDPartida
                WHERE pa.IDUsuario = :u AND pa.Estado = 'activo' AND p.Estado = 'finalizada' AND p.FechaFin >= :d";
        $parametros = [':u' => $idUsuario, ':d' => $desde];
        if ($ganadas) {
            $sql .= ' AND pa.Posicion = 1';
        }
        if ($lado !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM PARTIDA_DISTRITO pd INNER JOIN DISTRITOS d ON d.IDDistrito = pd.IDDistrito
                                  WHERE pd.IDPartida = p.IDPartida AND d.Lado = :l)';
            $parametros[':l'] = $lado;
        }
        $s = $this->db()->prepare($sql);
        $s->execute($parametros);
        return (int) $s->fetchColumn();
    }

    /* ---------- desafíos (etapa C) ---------- */

    public function registrarIntento(int $idPartida, int $idUsuario, string $idMision, int $paso): void
    {
        $this->db()->prepare('INSERT INTO MISION_PARTIDAS (IDPartida, IDUsuario, IDMision, Paso) VALUES (:p, :u, :m, :s)')
            ->execute([':p' => $idPartida, ':u' => $idUsuario, ':m' => $idMision, ':s' => $paso]);
    }

    /** Intentos de un paso, el más nuevo primero, con el estado de su partida. */
    public function intentos(int $idUsuario, string $idMision, int $paso): array
    {
        $s = $this->db()->prepare('SELECT mp.IDPartida, mp.Resultado, p.Estado FROM MISION_PARTIDAS mp INNER JOIN PARTIDAS p ON p.IDPartida = mp.IDPartida
            WHERE mp.IDUsuario = :u AND mp.IDMision = :m AND mp.Paso = :s ORDER BY mp.IDPartida DESC');
        $s->execute([':u' => $idUsuario, ':m' => $idMision, ':s' => $paso]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarIntento(int $idPartida, string $resultado): void
    {
        $this->db()->prepare('UPDATE MISION_PARTIDAS SET Resultado = :r WHERE IDPartida = :p AND Resultado IS NULL')->execute([':r' => $resultado, ':p' => $idPartida]);
    }

    public function resultadoDesafio(int $idPartida, int $idUsuario): ?array
    {
        $s = $this->db()->prepare('SELECT p.Estado AS EstadoPartida, pa.Estado AS EstadoJugador, pa.Posicion, pa.PuntajeFinal
            FROM PARTIDAS p INNER JOIN PARTICIPACIONES pa ON pa.IDPartida = p.IDPartida AND pa.IDUsuario = :u WHERE p.IDPartida = :p');
        $s->execute([':p' => $idPartida, ':u' => $idUsuario]);
        $f = $s->fetch(PDO::FETCH_ASSOC);
        return $f === false ? null : $f;
    }

    /** Fichas que quedaron en el tablero del jugador: [['tipo' => ..., 'recinto' => ...]]. */
    public function tableroFinal(int $idPartida, int $idUsuario): array
    {
        $s = $this->db()->prepare("SELECT f.Codigo AS tipo, r.Codigo AS recinto FROM FICHAS_PARTIDA fp
            INNER JOIN FICHAS f ON f.IDFicha = fp.IDFicha INNER JOIN RECINTOS r ON r.IDRecinto = fp.IDRecinto
            WHERE fp.IDPartida = :p AND fp.IDUsuario = :u AND fp.Ubicacion = 'tablero'");
        $s->execute([':p' => $idPartida, ':u' => $idUsuario]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function misionDePartida(int $idPartida): ?string
    {
        $s = $this->db()->prepare('SELECT IDMision FROM MISION_PARTIDAS WHERE IDPartida = :p');
        $s->execute([':p' => $idPartida]);
        $v = $s->fetchColumn();
        return $v === false ? null : (string) $v;
    }

    public function esDesafio(int $idPartida): bool
    {
        $s = $this->db()->prepare('SELECT 1 FROM MISION_PARTIDAS WHERE IDPartida = :p');
        $s->execute([':p' => $idPartida]);
        return $s->fetchColumn() !== false;
    }

    /** XP para el nivel y puntos al saldo de la tienda. No toca los puntos del ranking. */
    public function sumarRecompensa(int $idUsuario, int $xp, int $puntos): void
    {
        $s = $this->db()->prepare('UPDATE JUGADORES SET XP = XP + :x, PntsPartida = PntsPartida + :p WHERE IDUsuario = :u AND EsBot = 0');
        $s->execute([':x' => max(0, $xp), ':p' => max(0, $puntos), ':u' => $idUsuario]);
        $this->db()->prepare('UPDATE POSICIONES_MUNDO SET XP = XP + :x WHERE IDUsuario = :u')->execute([':x' => max(0, $xp), ':u' => $idUsuario]);
    }

    public function xpDe(int $idUsuario): int
    {
        $s = $this->db()->prepare('SELECT XP FROM JUGADORES WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        return (int) $s->fetchColumn();
    }

    /** [IDLogro => fecha] */
    public function logrosDe(int $idUsuario): array
    {
        $s = $this->db()->prepare('SELECT IDLogro, Fecha FROM LOGROS_JUGADOR WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        return array_column($s->fetchAll(PDO::FETCH_ASSOC), 'Fecha', 'IDLogro');
    }

    public function otorgarLogro(int $idUsuario, string $idLogro): bool
    {
        $s = $this->db()->prepare('INSERT IGNORE INTO LOGROS_JUGADOR (IDUsuario, IDLogro) VALUES (:u, :l)');
        $s->execute([':u' => $idUsuario, ':l' => $idLogro]);
        return $s->rowCount() === 1;
    }
}
?>
