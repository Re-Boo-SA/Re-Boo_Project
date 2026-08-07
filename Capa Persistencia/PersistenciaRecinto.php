<?php

require_once(__DIR__ . '/../DTO/RecintoDTO.php');
require_once('IPersistenciaRecinto.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaRecinto implements IPersistenciaRecinto
{
    private $conn;
    private $res;

    private static ?PersistenciaRecinto $instancia = null;

    public static function getInstancia(): PersistenciaRecinto
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
        throw new \Exception("No se puede deserializar el singletonRecinto");
    }

    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();
            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {
            echo "Error de conexión en PersistenciaRecinto: " . $e->getMessage();
        }
    }
}
?>