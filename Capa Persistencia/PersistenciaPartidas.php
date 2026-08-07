<?php

require_once(__DIR__ . '/../DTO/PartidasDTO.php');
require_once('IPersistenciaPartidas.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaPartidas implements IPersistenciaPartidas
{
    private $conn;
    private $res;

    private static ?PersistenciaPartidas $instancia = null;

    public static function getInstancia(): PersistenciaPartidas
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
        throw new \Exception("No se puede deserializar el singletonPartidas");
    }

    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();
            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {
            echo "Error de conexión en PersistenciaPartidas: " . $e->getMessage();
        }
    }
}
?>