<?php
require_once('IPersistenciaSucesos.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/* Premios de sucesos y datos para el diario del día (Berlín vivo, etapa B). */
class PersistenciaSucesos implements IPersistenciaSucesos
{
    private ?PDO $conn = null;
    private static ?PersistenciaSucesos $instancia = null;

    public static function getInstancia(): PersistenciaSucesos
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
        throw new \Exception("No se puede deserializar el singletonSucesos");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaSucesos: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('Los sucesos no están disponibles por un problema de conexión.');
        }
        return $this->conn;
    }

    /** false si ya lo había cobrado en este tramo. */
    public function cobrarPremio(int $idUsuario, int $tramo): bool
    {
        $s = $this->db()->prepare('INSERT IGNORE INTO SUCESOS_RECOMPENSAS (IDUsuario, Tramo) VALUES (:u, :t)');
        $s->execute([':u' => $idUsuario, ':t' => $tramo]);
        return $s->rowCount() === 1;
    }

    /** Quién sumó más puntos hoy en partidas terminadas (humanos). */
    public function figuraDelDia(): ?array
    {
        $f = $this->db()->query("SELECT u.NombreUsuario, SUM(pa.PuntosOtorgados) AS Puntos FROM PARTICIPACIONES pa
            INNER JOIN PARTIDAS p ON p.IDPartida = pa.IDPartida INNER JOIN USUARIOS u ON u.IDUsuario = pa.IDUsuario
            INNER JOIN JUGADORES j ON j.IDUsuario = pa.IDUsuario
            WHERE p.Estado = 'finalizada' AND DATE(p.FechaFin) = CURDATE() AND j.EsBot = 0 AND pa.PuntosOtorgados > 0
            GROUP BY u.IDUsuario, u.NombreUsuario ORDER BY Puntos DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return $f === false ? null : $f;
    }

    /** El último torneo terminado en los últimos 2 días. */
    public function ultimoCampeon(): ?array
    {
        $f = $this->db()->query("SELECT t.Nombre, u.NombreUsuario AS Campeon FROM TORNEOS t INNER JOIN USUARIOS u ON u.IDUsuario = t.IDGanador
            WHERE t.Estado = 'finalizado' AND t.FechaFin > NOW() - INTERVAL 2 DAY ORDER BY t.FechaFin DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return $f === false ? null : $f;
    }
}
?>
