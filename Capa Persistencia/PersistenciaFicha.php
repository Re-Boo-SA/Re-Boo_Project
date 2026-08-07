<?php

require_once(__DIR__ . '/../DTO/FichaDTO.php');
require_once('IPersistenciaFicha.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaFicha implements IPersistenciaFicha
{
    private $conn;
    private $res;

    private static ?PersistenciaFicha $instancia = null;

    public static function getInstancia(): PersistenciaFicha
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
        throw new \Exception("No se puede deserializar el singletonFicha");
    }

    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();
            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {
            echo "Error de conexión en PersistenciaFicha: " . $e->getMessage();
        }
    }
}
?>