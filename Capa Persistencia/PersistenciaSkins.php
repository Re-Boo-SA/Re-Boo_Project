<?php

require_once(__DIR__ . '/../DTO/SkinsDTO.php');
require_once('IPersistenciaSkins.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaSkins implements IPersistenciaSkins
{
    private $conn;
    private $res;

    private static ?PersistenciaSkins $instancia = null;

    public static function getInstancia(): PersistenciaSkins
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
        throw new \Exception("No se puede deserializar el singletonSkins");
    }

    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();
            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {
            echo "Error de conexión en PersistenciaSkins: " . $e->getMessage();
        }
    }
}
?>