<?php

require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once(__DIR__ . '/IPersistenciaAdmin.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaAdmin implements IPersistenciaAdmin
{
    private $conn = null;
    private static ?PersistenciaAdmin $instancia = null;

    public static function getInstancia(): PersistenciaAdmin
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();
            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {
            echo "Error de conexión en PersistenciaAdmin: " . $e->getMessage();
        }
    }

    public function buscarAdmin(int $idUsuario): ?Administrador
    {
        if ($this->conn === null) {
            return null;
        }

        $sql = "CALL buscarAdmin(?);";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idUsuario]);
            $reader = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($reader) {
                return new Administrador(
                    (int) $reader['IDUsuario'],
                    $reader['Correo'],
                    $reader['Contra'],
                    $reader['NombreUsuario'],
                    (bool) $reader['BajaLogica']
                );
            }
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            print ("Error al buscar admin: " . $e->getMessage());
        }

        return null;
    }

    public function listarAdmins(): array
    {
        $admins = [];
        if ($this->conn === null) {
            return $admins;
        }

        $sql = "CALL listarAdmin();";

        try {
            $stmt = $this->conn->query($sql);
            while ($reader = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $admins[] = new Administrador(
                    (int) $reader['IDUsuario'],
                    $reader['Correo'],
                    $reader['Contra'],
                    $reader['NombreUsuario'],
                    (bool) $reader['BajaLogica']
                );
            }
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            print ("Error al listar admins: " . $e->getMessage());
        }

        return $admins;
    }
}
?>