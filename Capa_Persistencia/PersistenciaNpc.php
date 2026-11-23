<?php
require_once('IPersistenciaNpc.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/* Confianza y recuerdos de cada contacto con cada jugador (Berlín vivo, etapa A). */
class PersistenciaNpc implements IPersistenciaNpc
{
    private ?PDO $conn = null;
    private static ?PersistenciaNpc $instancia = null;

    public static function getInstancia(): PersistenciaNpc
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
        throw new \Exception("No se puede deserializar el singletonNpc");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaNpc: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('Los contactos no están disponibles por un problema de conexión.');
        }
        return $this->conn;
    }

    public function confianza(int $idUsuario, string $idContacto): ?int
    {
        $s = $this->db()->prepare('SELECT Confianza FROM RELACION_NPC WHERE IDUsuario = :u AND IDContacto = :c');
        $s->execute([':u' => $idUsuario, ':c' => $idContacto]);
        $v = $s->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    public function guardarConfianza(int $idUsuario, string $idContacto, int $valor): void
    {
        $valor = max(-100, min(100, $valor));
        $this->db()->prepare('INSERT INTO RELACION_NPC (IDUsuario, IDContacto, Confianza) VALUES (:u, :c, :v)
            ON DUPLICATE KEY UPDATE Confianza = VALUES(Confianza)')->execute([':u' => $idUsuario, ':c' => $idContacto, ':v' => $valor]);
    }

    public function recuerdos(int $idUsuario, string $idContacto): array
    {
        $s = $this->db()->prepare('SELECT Clave FROM RECUERDOS_NPC WHERE IDUsuario = :u AND IDContacto = :c');
        $s->execute([':u' => $idUsuario, ':c' => $idContacto]);
        return array_column($s->fetchAll(PDO::FETCH_ASSOC), 'Clave');
    }

    /** false si ya lo recordaba (así un efecto se aplica una sola vez). */
    public function recordar(int $idUsuario, string $idContacto, string $clave): bool
    {
        $s = $this->db()->prepare('INSERT IGNORE INTO RECUERDOS_NPC (IDUsuario, IDContacto, Clave) VALUES (:u, :c, :k)');
        $s->execute([':u' => $idUsuario, ':c' => $idContacto, ':k' => mb_substr($clave, 0, 80)]);
        return $s->rowCount() === 1;
    }

    public function bandoDe(int $idUsuario): ?string
    {
        $s = $this->db()->prepare('SELECT Bando FROM JUGADORES WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        $v = $s->fetchColumn();
        return $v === false || $v === null ? null : (string) $v;
    }
}
?>
