<?php

require_once(__DIR__ . '/IPersistenciaAuditoria.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaAuditoria implements IPersistenciaAuditoria
{
    private $conn = null;
    private static ?PersistenciaAuditoria $instancia = null;

    public static function getInstancia(): PersistenciaAuditoria
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
        throw new \Exception("No se puede deserializar el singleton de PersistenciaAuditoria");
    }

    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();
            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {
            error_log("Error de conexión en PersistenciaAuditoria: " . $e->getMessage());
        }
    }

    public function registrarEvento(string $tipo, string $nivel, ?int $idUsuario, ?string $identificador, string $ip, ?string $detalle): bool
    {
        if ($this->conn === null) {
            return false;
        }

        $sql = "INSERT INTO AUDITORIA (Tipo, Nivel, IDUsuario, Identificador, IP, Detalle)
                VALUES (:tipo, :nivel, :idUsuario, :identificador, :ip, :detalle)";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':tipo' => mb_substr($tipo, 0, 40),
                ':nivel' => $nivel,
                ':idUsuario' => $idUsuario,
                ':identificador' => $identificador === null ? null : mb_substr($identificador, 0, 100),
                ':ip' => mb_substr($ip, 0, 45),
                ':detalle' => $detalle === null ? null : mb_substr($detalle, 0, 255)
            ]);
        } catch (\PDOException $e) {
            error_log("Error al registrar evento de auditoría: " . $e->getMessage());
            return false;
        }
    }

    public function contarEventos(string $tipo, int $minutos, ?string $ip = null, ?string $identificador = null, ?int $idUsuario = null): int
    {
        if ($this->conn === null) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM AUDITORIA WHERE Tipo = ? AND Fecha >= NOW() - INTERVAL ? MINUTE";
        $parametros = [$tipo, $minutos];

        if ($ip !== null) {
            $sql .= " AND IP = ?";
            $parametros[] = $ip;
        }
        if ($identificador !== null) {
            $sql .= " AND Identificador = ?";
            $parametros[] = $identificador;
        }
        if ($idUsuario !== null) {
            $sql .= " AND IDUsuario = ?";
            $parametros[] = $idUsuario;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($parametros);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error al contar eventos de auditoría: " . $e->getMessage());
            return 0;
        }
    }

    public function listarEventos(int $limite = 200, string $tipo = ''): array
    {
        $eventos = [];
        if ($this->conn === null) {
            return $eventos;
        }

        $limite = max(1, min($limite, 1000));
        $sql = "SELECT IDEvento, Fecha, Tipo, Nivel, IDUsuario, Identificador, IP, Detalle FROM AUDITORIA";
        $parametros = [];
        if ($tipo !== '') {
            $sql .= " WHERE Tipo = ?";
            $parametros[] = $tipo;
        }
        $sql .= " ORDER BY IDEvento DESC LIMIT " . $limite;

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($parametros);
            while ($reader = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $eventos[] = $reader;
            }
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            error_log("Error al listar eventos de auditoría: " . $e->getMessage());
        }

        return $eventos;
    }

    public function ping(): bool
    {
        if ($this->conn === null) {
            return false;
        }

        try {
            return (int) $this->conn->query('SELECT 1')->fetchColumn() === 1;
        } catch (\PDOException $e) {
            error_log("La base de datos no responde: " . $e->getMessage());
            return false;
        }
    }
}
?>
