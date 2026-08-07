<?php

require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once('IPersistenciaAdmin.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaAdmin implements IPersistenciaAdmin
{

    private $conn;
    private $res;

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

    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar el singletonAdmin");
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

}
?>