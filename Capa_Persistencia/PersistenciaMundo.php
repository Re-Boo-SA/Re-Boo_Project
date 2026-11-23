<?php
require_once('IPersistenciaMundo.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/* SQL del mundo abierto: temporadas, distritos, influencia, posiciones y chat. */
class PersistenciaMundo implements IPersistenciaMundo
{
    private ?PDO $conn = null;
    private static ?PersistenciaMundo $instancia = null;

    public static function getInstancia(): PersistenciaMundo
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
        throw new \Exception("No se puede deserializar el singletonMundo");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaMundo: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('El mundo no está disponible por un problema de conexión.');
        }
        return $this->conn;
    }

    /* ---------- Temporadas ---------- */

    public function ultimaTemporada(): ?array
    {
        $fila = $this->db()->query(
            'SELECT IDTemporada, Numero, Cerrada, BandoGanador, TIMESTAMPDIFF(SECOND, NOW(), FechaFin) AS SegundosRestantes
             FROM TEMPORADAS ORDER BY Numero DESC LIMIT 1'
        )->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function crearTemporada(int $numero): void
    {
        $s = $this->db()->prepare('INSERT INTO TEMPORADAS (Numero, FechaInicio, FechaFin) VALUES (:n, NOW(), NOW() + INTERVAL 30 DAY)');
        $s->execute([':n' => $numero]);
    }

    public function cerrarTemporada(int $idTemporada, string $ganador, int $distritosOeste, int $distritosEste): void
    {
        $s = $this->db()->prepare(
            'UPDATE TEMPORADAS SET Cerrada = 1, BandoGanador = :g, DistritosOeste = :o, DistritosEste = :e
             WHERE IDTemporada = :t AND Cerrada = 0'
        );
        $s->execute([':g' => $ganador, ':o' => $distritosOeste, ':e' => $distritosEste, ':t' => $idTemporada]);
    }

    /* ---------- Distritos e influencia ---------- */

    public function distritos(): array
    {
        return $this->db()->query(
            'SELECT IDDistrito, Codigo, Nombre, Lado, X, Y, Ancho, Alto, CafeX, CafeY FROM DISTRITOS ORDER BY IDDistrito'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function influencia(int $idTemporada): array
    {
        $s = $this->db()->prepare('SELECT IDDistrito, Bando, Puntos FROM INFLUENCIA WHERE IDTemporada = :t');
        $s->execute([':t' => $idTemporada]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sumarInfluencia(int $idTemporada, int $idDistrito, string $bando, int $puntos): void
    {
        $s = $this->db()->prepare(
            'INSERT INTO INFLUENCIA (IDTemporada, IDDistrito, Bando, Puntos) VALUES (:t, :d, :b, :p)
             ON DUPLICATE KEY UPDATE Puntos = Puntos + VALUES(Puntos)'
        );
        $s->execute([':t' => $idTemporada, ':d' => $idDistrito, ':b' => $bando, ':p' => $puntos]);
    }

    /* ---------- Bandos ---------- */

    public function bandoDe(int $idUsuario): ?array
    {
        $s = $this->db()->prepare('SELECT Bando, IDTemporadaBando FROM JUGADORES WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        $fila = $s->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function elegirBando(int $idUsuario, string $bando, int $idTemporada): void
    {
        $s = $this->db()->prepare('UPDATE JUGADORES SET Bando = :b, IDTemporadaBando = :t WHERE IDUsuario = :u AND EsBot = 0');
        $s->execute([':b' => $bando, ':t' => $idTemporada, ':u' => $idUsuario]);
    }

    /* ---------- Posiciones ---------- */

    public function posicion(int $idUsuario): ?array
    {
        $s = $this->db()->prepare(
            'SELECT X, Y, Direccion, TIMESTAMPDIFF(MICROSECOND, UltimaActividad, NOW(3)) / 1000000 AS Segundos
             FROM POSICIONES_MUNDO WHERE IDUsuario = :u'
        );
        $s->execute([':u' => $idUsuario]);
        $fila = $s->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    /** Mueve a alguien que ya está en el mapa. false = no tenía fila (por ejemplo, MySQL se reinició). */
    public function guardarPosicion(int $idUsuario, int $x, int $y, int $direccion): bool
    {
        // Si se movió, deja de hacer el gesto (se evalúa antes de cambiar X e Y).
        $s = $this->db()->prepare('UPDATE POSICIONES_MUNDO SET Gesto = IF(X <> :x0 OR Y <> :y0, 0, Gesto), X = :x, Y = :y, Direccion = :d, UltimaActividad = NOW(3) WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario, ':x0' => $x, ':y0' => $y, ':x' => $x, ':y' => $y, ':d' => $direccion]);
        return $s->rowCount() > 0;
    }

    /** Pone (o vuelve a poner) a alguien en el mapa, copiando nombre, bando y MMR de las tablas de verdad. */
    public function crearPosicion(int $idUsuario, int $x, int $y, int $direccion): void
    {
        $s = $this->db()->prepare(
            'INSERT INTO POSICIONES_MUNDO (IDUsuario, Nombre, Bando, MMR, Visible, Apariencia, Personal, XP, X, Y, Direccion, UltimaActividad)
             SELECT u.IDUsuario, u.NombreUsuario, j.Bando, j.MMR, (j.Estado <> \'invisible\'),
                    COALESCE(a.Piel * 12 + a.Pelo * 3 + a.Gorro, 0), a.Personal, j.XP, :x, :y, :d, NOW(3)
             FROM USUARIOS u INNER JOIN JUGADORES j ON j.IDUsuario = u.IDUsuario
             LEFT JOIN APARIENCIA_JUGADOR a ON a.IDUsuario = u.IDUsuario
             WHERE u.IDUsuario = :u
             ON DUPLICATE KEY UPDATE Nombre = VALUES(Nombre), Bando = VALUES(Bando), MMR = VALUES(MMR), Visible = VALUES(Visible), Apariencia = VALUES(Apariencia), Personal = VALUES(Personal), XP = VALUES(XP),
                 X = VALUES(X), Y = VALUES(Y), Direccion = VALUES(Direccion), UltimaActividad = VALUES(UltimaActividad)'
        );
        $s->execute([':u' => $idUsuario, ':x' => $x, ':y' => $y, ':d' => $direccion]);
    }

    /** Actualiza la copia de bando, MMR y visibilidad (se llama cada pocos segundos, no en cada paso). */
    public function refrescarPerfil(int $idUsuario): void
    {
        $s = $this->db()->prepare(
            'UPDATE POSICIONES_MUNDO p INNER JOIN JUGADORES j ON j.IDUsuario = p.IDUsuario
             LEFT JOIN APARIENCIA_JUGADOR a ON a.IDUsuario = p.IDUsuario
             SET p.Bando = j.Bando, p.MMR = j.MMR, p.Visible = (j.Estado <> \'invisible\'),
                 p.Apariencia = COALESCE(a.Piel * 12 + a.Pelo * 3 + a.Gorro, 0), p.Personal = a.Personal, p.XP = j.XP
             WHERE p.IDUsuario = :u'
        );
        $s->execute([':u' => $idUsuario]);
    }

    /** Saca del mapa a quien lleva más de 5 minutos sin dar señales. */
    public function purgarPosiciones(): void
    {
        $this->db()->exec('DELETE FROM POSICIONES_MUNDO WHERE UltimaActividad < NOW(3) - INTERVAL 5 MINUTE');
    }

    /** Jugadores que dieron señales en los últimos $segundos. Sin joins: todo está en la propia tabla. */
    public function jugadoresActivos(int $segundos): array
    {
        $s = $this->db()->prepare(
            'SELECT IDUsuario, Nombre AS NombreUsuario, Bando, MMR, Apariencia, Personal, XP, X, Y, Direccion, IF(GestoHasta > NOW(3), Gesto, 0) AS Gesto
             FROM POSICIONES_MUNDO WHERE UltimaActividad > NOW(3) - INTERVAL :s SECOND AND Visible = 1 LIMIT 200'
        );
        $s->execute([':s' => $segundos]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Gesto por unos segundos (o hasta que se mueva, si es sentarse). */
    public function gesto(int $idUsuario, int $gesto, int $segundos): bool
    {
        $s = $this->db()->prepare('UPDATE POSICIONES_MUNDO SET Gesto = :g, GestoHasta = NOW(3) + INTERVAL :s SECOND WHERE IDUsuario = :u');
        $s->execute([':g' => $gesto, ':s' => $segundos, ':u' => $idUsuario]);
        return $s->rowCount() > 0;
    }

    /* ---------- Apariencia ---------- */

    public function apariencia(int $idUsuario): ?array
    {
        $s = $this->db()->prepare('SELECT Piel, Pelo, Gorro FROM APARIENCIA_JUGADOR WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        $fila = $s->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : array_map('intval', $fila);
    }

    /** Personaje personalizado (código corto, ver js/apariencia.js). */
    public function personaje(int $idUsuario): ?string
    {
        $s = $this->db()->prepare('SELECT Personal FROM APARIENCIA_JUGADOR WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        $v = $s->fetchColumn();
        return $v === false || $v === null ? null : (string) $v;
    }

    public function guardarPersonaje(int $idUsuario, string $codigo): void
    {
        $this->db()->prepare('INSERT INTO APARIENCIA_JUGADOR (IDUsuario, Personal) VALUES (:u, :c) ON DUPLICATE KEY UPDATE Personal = VALUES(Personal)')
            ->execute([':u' => $idUsuario, ':c' => $codigo]);
        $this->db()->prepare('UPDATE POSICIONES_MUNDO SET Personal = :c WHERE IDUsuario = :u')->execute([':u' => $idUsuario, ':c' => $codigo]);
    }

    public function guardarApariencia(int $idUsuario, int $piel, int $pelo, int $gorro): void
    {
        $s = $this->db()->prepare(
            'INSERT INTO APARIENCIA_JUGADOR (IDUsuario, Piel, Pelo, Gorro) VALUES (:u, :p, :pe, :g)
             ON DUPLICATE KEY UPDATE Piel = VALUES(Piel), Pelo = VALUES(Pelo), Gorro = VALUES(Gorro)'
        );
        $s->execute([':u' => $idUsuario, ':p' => $piel, ':pe' => $pelo, ':g' => $gorro]);
    }

    /* ---------- Chat ---------- */

    /** Mensajes de los últimos $segundos con id mayor a $desde (el navegador pide solo los nuevos). */
    public function mensajesRecientes(int $segundos, int $desde = 0): array
    {
        $s = $this->db()->prepare(
            'SELECT IDMensaje, IDUsuario, Texto FROM MENSAJES_MUNDO
             WHERE IDMensaje > :d AND Fecha > NOW() - INTERVAL :s SECOND ORDER BY IDMensaje LIMIT 50'
        );
        $s->execute([':s' => $segundos, ':d' => $desde]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function decir(int $idUsuario, string $texto): void
    {
        $s = $this->db()->prepare('INSERT INTO MENSAJES_MUNDO (IDUsuario, Texto) VALUES (:u, :t)');
        $s->execute([':u' => $idUsuario, ':t' => $texto]);
    }

    public function mensaje(int $idMensaje): ?array
    {
        $s = $this->db()->prepare(
            'SELECT m.IDMensaje, m.IDUsuario, m.Texto, u.NombreUsuario FROM MENSAJES_MUNDO m
             INNER JOIN USUARIOS u ON u.IDUsuario = m.IDUsuario WHERE m.IDMensaje = :m'
        );
        $s->execute([':m' => $idMensaje]);
        $fila = $s->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function purgarMensajes(): void
    {
        $this->db()->exec('DELETE FROM MENSAJES_MUNDO WHERE Fecha < NOW() - INTERVAL 10 MINUTE');
    }

    public function reportar(string $motivo): void
    {
        $s = $this->db()->prepare('INSERT INTO REPORTES (Motivo) VALUES (:m)');
        $s->execute([':m' => mb_substr($motivo, 0, 255)]);
    }

    /* ---------- Partidas y distritos ---------- */

    public function asignarDistrito(int $idPartida, int $idDistrito): void
    {
        $s = $this->db()->prepare('INSERT INTO PARTIDA_DISTRITO (IDPartida, IDDistrito) VALUES (:p, :d)');
        $s->execute([':p' => $idPartida, ':d' => $idDistrito]);
    }

    public function distritoDePartida(int $idPartida): ?int
    {
        $s = $this->db()->prepare('SELECT IDDistrito FROM PARTIDA_DISTRITO WHERE IDPartida = :p');
        $s->execute([':p' => $idPartida]);
        $id = $s->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function salasDeDistrito(int $idDistrito): array
    {
        $s = $this->db()->prepare(
            "SELECT p.IDPartida, p.Nombre, p.Tipo, p.Modo, p.MaxJugadores, u.NombreUsuario AS Creador, COUNT(pa.IDUsuario) AS Jugadores
             FROM PARTIDAS p
             INNER JOIN PARTIDA_DISTRITO pd ON pd.IDPartida = p.IDPartida AND pd.IDDistrito = :d
             INNER JOIN PARTICIPACIONES pa ON pa.IDPartida = p.IDPartida
             LEFT JOIN USUARIOS u ON u.IDUsuario = p.IDCreador
             WHERE p.Estado = 'esperando' AND p.BajaLogica = 0
             GROUP BY p.IDPartida, p.Nombre, p.Tipo, p.Modo, p.MaxJugadores, u.NombreUsuario
             HAVING COUNT(pa.IDUsuario) < p.MaxJugadores
             ORDER BY p.IDPartida DESC LIMIT 20"
        );
        $s->execute([':d' => $idDistrito]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Datos de cada participante de una partida terminada, con su bando y su última posición. */
    public function participantesParaInfluencia(int $idPartida): array
    {
        $s = $this->db()->prepare(
            'SELECT pa.IDUsuario, pa.Estado, pa.Posicion, pa.PuntosOtorgados, j.EsBot, j.Bando, pm.X, pm.Y
             FROM PARTICIPACIONES pa
             INNER JOIN JUGADORES j ON j.IDUsuario = pa.IDUsuario
             LEFT JOIN POSICIONES_MUNDO pm ON pm.IDUsuario = pa.IDUsuario
             WHERE pa.IDPartida = :p'
        );
        $s->execute([':p' => $idPartida]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Bono del 10 % por jugar en un distrito dominado por tu bando. */
    public function sumarBonoTienda(int $idPartida, int $idUsuario, int $puntos): void
    {
        $db = $this->db();
        $db->prepare('UPDATE JUGADORES SET PntsPartida = PntsPartida + :p WHERE IDUsuario = :u AND EsBot = 0')
            ->execute([':p' => $puntos, ':u' => $idUsuario]);
        $db->prepare('UPDATE PARTICIPACIONES SET PuntosOtorgados = PuntosOtorgados + :p WHERE IDPartida = :pa AND IDUsuario = :u')
            ->execute([':p' => $puntos, ':pa' => $idPartida, ':u' => $idUsuario]);
    }
}
?>
