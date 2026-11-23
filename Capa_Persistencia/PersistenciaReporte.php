<?php
require_once('IPersistenciaReporte.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/*
 * SQL de los reportes (RF27) y de su moderación (RF26).
 * Quién atendió cada reporte queda en administra_reportes.
 */
class PersistenciaReporte implements IPersistenciaReporte
{
    private ?PDO $conn = null;
    private static ?PersistenciaReporte $instancia = null;

    public static function getInstancia(): PersistenciaReporte
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
        throw new \Exception("No se puede deserializar el singletonReporte");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaReporte: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('Los reportes no están disponibles por un problema de conexión.');
        }
        return $this->conn;
    }

    public function crear(string $tipo, string $motivo, ?string $descripcion, ?int $idReportante, ?int $idDenunciado, ?int $idPartida): int
    {
        $db = $this->db();
        $s = $db->prepare(
            'INSERT INTO REPORTES (Tipo, Motivo, Descripcion, IDReportante, IDDenunciado, IDPartida)
             VALUES (:t, :m, :d, :r, :den, :p)'
        );
        $s->execute([
            ':t' => $tipo,
            ':m' => mb_substr($motivo, 0, 255),
            ':d' => $descripcion === null ? null : mb_substr($descripcion, 0, 1000),
            ':r' => $idReportante,
            ':den' => $idDenunciado,
            ':p' => $idPartida,
        ]);
        return (int) $db->lastInsertId();
    }

    public function listarDeUsuario(int $idUsuario, int $limite = 20): array
    {
        $s = $this->db()->prepare(
            "SELECT r.IDReportes, r.Tipo, r.Motivo, r.Estado, r.Fecha, r.IDPartida, d.NombreUsuario AS Denunciado
             FROM REPORTES r
             LEFT JOIN USUARIOS d ON d.IDUsuario = r.IDDenunciado
             WHERE r.IDReportante = :u AND r.BajaLogica = 0
             ORDER BY r.Fecha DESC, r.IDReportes DESC LIMIT " . max(1, min(100, $limite))
        );
        $s->execute([':u' => $idUsuario]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarParaAdmin(string $estado, int $limite = 100): array
    {
        $filtro = in_array($estado, ['pendiente', 'resuelto', 'descartado'], true) ? 'AND r.Estado = :e' : '';
        $s = $this->db()->prepare(
            "SELECT r.IDReportes, r.Tipo, r.Motivo, r.Descripcion, r.Estado, r.Fecha, r.FechaResolucion, r.IDPartida,
                    rep.NombreUsuario AS Reportante, den.NombreUsuario AS Denunciado, den.IDUsuario AS IDDenunciado,
                    p.Nombre AS NombrePartida,
                    (SELECT u.NombreUsuario FROM administra_reportes a INNER JOIN USUARIOS u ON u.IDUsuario = a.IDUsuario
                     WHERE a.IDReportes = r.IDReportes LIMIT 1) AS Admin
             FROM REPORTES r
             LEFT JOIN USUARIOS rep ON rep.IDUsuario = r.IDReportante
             LEFT JOIN USUARIOS den ON den.IDUsuario = r.IDDenunciado
             LEFT JOIN PARTIDAS p ON p.IDPartida = r.IDPartida
             WHERE r.BajaLogica = 0 $filtro
             ORDER BY (r.Estado = 'pendiente') DESC, r.Fecha DESC, r.IDReportes DESC
             LIMIT " . max(1, min(500, $limite))
        );
        $s->execute($filtro === '' ? [] : [':e' => $estado]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorEstado(): array
    {
        $conteo = ['pendiente' => 0, 'resuelto' => 0, 'descartado' => 0];
        foreach ($this->db()->query('SELECT Estado, COUNT(*) AS N FROM REPORTES WHERE BajaLogica = 0 GROUP BY Estado') as $fila) {
            $conteo[$fila['Estado']] = (int) $fila['N'];
        }
        return $conteo;
    }

    public function cerrar(int $idReporte, int $idAdmin, string $estado): bool
    {
        $db = $this->db();
        $propia = !$db->inTransaction();
        if ($propia) {
            $db->beginTransaction();
        }
        try {
            $s = $db->prepare(
                "UPDATE REPORTES SET Estado = :e, FechaResolucion = NOW()
                 WHERE IDReportes = :r AND Estado = 'pendiente' AND BajaLogica = 0"
            );
            $s->execute([':e' => $estado, ':r' => $idReporte]);
            if ($s->rowCount() !== 1) {
                if ($propia) {
                    $db->rollBack();
                }
                return false;
            }
            $db->prepare('INSERT IGNORE INTO administra_reportes (IDUsuario, IDReportes) VALUES (:a, :r)')
                ->execute([':a' => $idAdmin, ':r' => $idReporte]);
            if ($propia) {
                $db->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($propia && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function idUsuarioPorNombre(string $nombre): ?int
    {
        $s = $this->db()->prepare('SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario = :n AND BajaLogica = 0 LIMIT 1');
        $s->execute([':n' => $nombre]);
        $id = $s->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function participoEnPartida(int $idUsuario, int $idPartida): bool
    {
        $s = $this->db()->prepare('SELECT 1 FROM PARTICIPACIONES WHERE IDUsuario = :u AND IDPartida = :p');
        $s->execute([':u' => $idUsuario, ':p' => $idPartida]);
        return $s->fetchColumn() !== false;
    }
}
?>
