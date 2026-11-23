<?php
require_once('IPersistenciaCola.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/* Cola del modo competitivo (tabla COLA_COMPETITIVA). */
class PersistenciaCola implements IPersistenciaCola
{
    private ?PDO $conn = null;
    private static ?PersistenciaCola $instancia = null;

    public static function getInstancia(): PersistenciaCola
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
        throw new \Exception("No se puede deserializar el singletonCola");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaCola: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('La cola competitiva no está disponible por un problema de conexión.');
        }
        return $this->conn;
    }

    public function iniciarTransaccion(): void { $this->db()->beginTransaction(); }
    public function confirmar(): void { if ($this->db()->inTransaction()) { $this->db()->commit(); } }
    public function revertir(): void { if ($this->db()->inTransaction()) { $this->db()->rollBack(); } }

    public function entrar(int $idUsuario): void
    {
        $s = $this->db()->prepare(
            'INSERT INTO COLA_COMPETITIVA (IDUsuario, FechaIngreso, UltimoLatido, IDPartidaAsignada) VALUES (:u, NOW(), NOW(), NULL)
             ON DUPLICATE KEY UPDATE FechaIngreso = NOW(), UltimoLatido = NOW(), IDPartidaAsignada = NULL'
        );
        $s->execute([':u' => $idUsuario]);
    }

    public function salir(int $idUsuario): void
    {
        $this->db()->prepare('DELETE FROM COLA_COMPETITIVA WHERE IDUsuario = :u')->execute([':u' => $idUsuario]);
    }

    public function latido(int $idUsuario): void
    {
        $this->db()->prepare('UPDATE COLA_COMPETITIVA SET UltimoLatido = NOW() WHERE IDUsuario = :u')->execute([':u' => $idUsuario]);
    }

    public function filaDe(int $idUsuario): ?array
    {
        $s = $this->db()->prepare(
            'SELECT IDUsuario, IDPartidaAsignada, TIMESTAMPDIFF(SECOND, FechaIngreso, NOW()) AS Segundos
             FROM COLA_COMPETITIVA WHERE IDUsuario = :u'
        );
        $s->execute([':u' => $idUsuario]);
        $fila = $s->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    /** Saca de la cola a quien cerró la página (sin latido) y nunca fue asignado. */
    public function limpiarInactivos(int $segundos): void
    {
        $s = $this->db()->prepare(
            'DELETE FROM COLA_COMPETITIVA WHERE IDPartidaAsignada IS NULL AND UltimoLatido < NOW() - INTERVAL :s SECOND'
        );
        $s->execute([':s' => $segundos]);
    }

    /** Los que esperan, del más antiguo al más nuevo, bloqueados para emparejar sin carreras. */
    public function esperandoBloqueados(): array
    {
        $sql = 'SELECT c.IDUsuario, j.MMR, TIMESTAMPDIFF(SECOND, c.FechaIngreso, NOW()) AS Segundos
                FROM COLA_COMPETITIVA c INNER JOIN JUGADORES j ON j.IDUsuario = c.IDUsuario
                WHERE c.IDPartidaAsignada IS NULL
                ORDER BY c.FechaIngreso
                FOR UPDATE';
        $filas = $this->db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($f) => ['id' => (int) $f['IDUsuario'], 'mmr' => (int) $f['MMR'], 'segundos' => (int) $f['Segundos']], $filas);
    }

    public function asignar(array $idsUsuarios, int $idPartida): void
    {
        $s = $this->db()->prepare('UPDATE COLA_COMPETITIVA SET IDPartidaAsignada = :p WHERE IDUsuario = :u');
        foreach ($idsUsuarios as $id) {
            $s->execute([':p' => $idPartida, ':u' => $id]);
        }
    }

    public function cantidadEsperando(): int
    {
        return (int) $this->db()->query('SELECT COUNT(*) FROM COLA_COMPETITIVA WHERE IDPartidaAsignada IS NULL')->fetchColumn();
    }
}
?>
