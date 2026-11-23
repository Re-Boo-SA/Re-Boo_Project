<?php
require_once('IPersistenciaTorneos.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/* Torneos relámpago (Fase 4). */
class PersistenciaTorneos implements IPersistenciaTorneos
{
    private ?PDO $conn = null;
    private static ?PersistenciaTorneos $instancia = null;

    public static function getInstancia(): PersistenciaTorneos
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
        throw new \Exception("No se puede deserializar el singletonTorneos");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaTorneos: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('Los torneos no están disponibles por un problema de conexión.');
        }
        return $this->conn;
    }

    private function filas(string $sql, array $p = []): array
    {
        $s = $this->db()->prepare($sql);
        $s->execute($p);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ejecutar(string $sql, array $p = []): void
    {
        $this->db()->prepare($sql)->execute($p);
    }

    public function iniciarTransaccion(): void { $this->db()->beginTransaction(); }
    public function confirmar(): void { if ($this->db()->inTransaction()) { $this->db()->commit(); } }
    public function revertir(): void { if ($this->db()->inTransaction()) { $this->db()->rollBack(); } }

    public function crear(string $nombre, int $tamano, int $idCreador, int $minutos): int
    {
        $this->ejecutar('INSERT INTO TORNEOS (Nombre, Tamano, IDCreador, FechaInicio) VALUES (:n, :t, :c, NOW() + INTERVAL :m MINUTE)',
            [':n' => $nombre, ':t' => $tamano, ':c' => $idCreador, ':m' => $minutos]);
        return (int) $this->db()->lastInsertId();
    }

    public function activosCreadosPor(int $idUsuario): int
    {
        $f = $this->filas("SELECT COUNT(*) AS n FROM TORNEOS WHERE IDCreador = :u AND Estado IN ('inscripcion', 'en_curso')", [':u' => $idUsuario]);
        return (int) $f[0]['n'];
    }

    /** Con $bloquear la fila queda tomada hasta el final de la transacción. Vencido = ya pasó la hora de inicio (reloj de MySQL). */
    public function torneo(int $idTorneo, bool $bloquear = false): ?array
    {
        $f = $this->filas('SELECT t.*, (t.FechaInicio <= NOW()) AS Vencido, TIMESTAMPDIFF(SECOND, NOW(), t.FechaInicio) AS SegundosParaEmpezar,
                c.NombreUsuario AS Creador, g.NombreUsuario AS Ganador
            FROM TORNEOS t INNER JOIN USUARIOS c ON c.IDUsuario = t.IDCreador LEFT JOIN USUARIOS g ON g.IDUsuario = t.IDGanador
            WHERE t.IDTorneo = :t' . ($bloquear ? ' FOR UPDATE' : ''), [':t' => $idTorneo]);
        return $f[0] ?? null;
    }

    public function listar(): array
    {
        return $this->filas("SELECT t.IDTorneo, t.Nombre, t.Tamano, t.Estado, t.Ronda, t.FechaInicio, TIMESTAMPDIFF(SECOND, NOW(), t.FechaInicio) AS SegundosParaEmpezar,
                c.NombreUsuario AS Creador, g.NombreUsuario AS Ganador, (SELECT COUNT(*) FROM TORNEO_JUGADORES j WHERE j.IDTorneo = t.IDTorneo) AS Inscriptos
            FROM TORNEOS t INNER JOIN USUARIOS c ON c.IDUsuario = t.IDCreador LEFT JOIN USUARIOS g ON g.IDUsuario = t.IDGanador
            WHERE t.Estado IN ('inscripcion', 'en_curso') OR t.FechaCreacion > NOW() - INTERVAL 2 DAY
            ORDER BY FIELD(t.Estado, 'en_curso', 'inscripcion', 'finalizado', 'cancelado'), t.FechaInicio DESC LIMIT 30");
    }

    public function inscriptos(int $idTorneo): array
    {
        return $this->filas('SELECT tj.IDUsuario, u.NombreUsuario, j.MMR, tj.Semilla, tj.Eliminado FROM TORNEO_JUGADORES tj
            INNER JOIN USUARIOS u ON u.IDUsuario = tj.IDUsuario INNER JOIN JUGADORES j ON j.IDUsuario = tj.IDUsuario
            WHERE tj.IDTorneo = :t ORDER BY tj.Semilla IS NULL, tj.Semilla, tj.Fecha', [':t' => $idTorneo]);
    }

    public function inscribir(int $idTorneo, int $idUsuario): void
    {
        $this->ejecutar('INSERT INTO TORNEO_JUGADORES (IDTorneo, IDUsuario) VALUES (:t, :u)', [':t' => $idTorneo, ':u' => $idUsuario]);
    }

    public function desinscribir(int $idTorneo, int $idUsuario): void
    {
        $this->ejecutar('DELETE FROM TORNEO_JUGADORES WHERE IDTorneo = :t AND IDUsuario = :u', [':t' => $idTorneo, ':u' => $idUsuario]);
    }

    public function fijarSemilla(int $idTorneo, int $idUsuario, int $semilla): void
    {
        $this->ejecutar('UPDATE TORNEO_JUGADORES SET Semilla = :s WHERE IDTorneo = :t AND IDUsuario = :u', [':s' => $semilla, ':t' => $idTorneo, ':u' => $idUsuario]);
    }

    public function eliminar(int $idTorneo, int $idUsuario): void
    {
        $this->ejecutar('UPDATE TORNEO_JUGADORES SET Eliminado = 1 WHERE IDTorneo = :t AND IDUsuario = :u', [':t' => $idTorneo, ':u' => $idUsuario]);
    }

    public function cambiarEstado(int $idTorneo, string $estado, int $ronda, ?int $idGanador): void
    {
        $this->ejecutar("UPDATE TORNEOS SET Estado = :e, Ronda = :r, IDGanador = :g,
                FechaFin = IF(:e2 IN ('finalizado', 'cancelado'), NOW(), NULL) WHERE IDTorneo = :t",
            [':e' => $estado, ':r' => $ronda, ':g' => $idGanador, ':e2' => $estado, ':t' => $idTorneo]);
    }

    public function llaves(int $idTorneo): array
    {
        return $this->filas('SELECT l.*, a.NombreUsuario AS NombreA, b.NombreUsuario AS NombreB, p.Estado AS EstadoPartida
            FROM TORNEO_LLAVES l INNER JOIN USUARIOS a ON a.IDUsuario = l.IDJugadorA LEFT JOIN USUARIOS b ON b.IDUsuario = l.IDJugadorB
            LEFT JOIN PARTIDAS p ON p.IDPartida = l.IDPartida WHERE l.IDTorneo = :t ORDER BY l.Ronda, l.Posicion', [':t' => $idTorneo]);
    }

    public function crearLlave(int $idTorneo, int $ronda, int $posicion, int $a, ?int $b, ?int $idPartida, ?int $ganador, ?string $resultado): void
    {
        $this->ejecutar('INSERT INTO TORNEO_LLAVES (IDTorneo, Ronda, Posicion, IDJugadorA, IDJugadorB, IDPartida, IDGanador, Resultado)
            VALUES (:t, :r, :p, :a, :b, :pa, :g, :res)',
            [':t' => $idTorneo, ':r' => $ronda, ':p' => $posicion, ':a' => $a, ':b' => $b, ':pa' => $idPartida, ':g' => $ganador, ':res' => $resultado]);
    }

    public function resolverLlave(int $idTorneo, int $ronda, int $posicion, int $ganador, string $resultado): void
    {
        $this->ejecutar('UPDATE TORNEO_LLAVES SET IDGanador = :g, Resultado = :res WHERE IDTorneo = :t AND Ronda = :r AND Posicion = :p AND IDGanador IS NULL',
            [':g' => $ganador, ':res' => $resultado, ':t' => $idTorneo, ':r' => $ronda, ':p' => $posicion]);
    }

    /** Estado de la partida y posición final de cada jugador (las abandonadas quedan sin posición). */
    public function resultadoPartida(int $idPartida): ?array
    {
        $p = $this->filas('SELECT Estado FROM PARTIDAS WHERE IDPartida = :p', [':p' => $idPartida]);
        if ($p === []) {
            return null;
        }
        return ['estado' => $p[0]['Estado'], 'posiciones' => array_column($this->filas('SELECT IDUsuario, Posicion FROM PARTICIPACIONES WHERE IDPartida = :p', [':p' => $idPartida]), 'Posicion', 'IDUsuario')];
    }

    public function torneosActivosDe(int $idUsuario): array
    {
        return array_map('intval', array_column($this->filas("SELECT t.IDTorneo FROM TORNEOS t INNER JOIN TORNEO_JUGADORES j ON j.IDTorneo = t.IDTorneo
            WHERE j.IDUsuario = :u AND j.Eliminado = 0 AND t.Estado IN ('inscripcion', 'en_curso')", [':u' => $idUsuario]), 'IDTorneo'));
    }

    public function torneoDePartida(int $idPartida): ?int
    {
        $f = $this->filas('SELECT IDTorneo FROM TORNEO_LLAVES WHERE IDPartida = :p', [':p' => $idPartida]);
        return $f === [] ? null : (int) $f[0]['IDTorneo'];
    }

    public function ganados(int $idUsuario): int
    {
        $f = $this->filas("SELECT COUNT(*) AS n FROM TORNEOS WHERE IDGanador = :u AND Estado = 'finalizado'", [':u' => $idUsuario]);
        return (int) $f[0]['n'];
    }
}
?>
