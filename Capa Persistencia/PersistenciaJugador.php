<?php

require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once('IPersistenciaJugador.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaJugador implements IPersistenciaJugador
{
    private $conn;
    private $res;

    private static ?PersistenciaJugador $instancia = null;

    public static function getInstancia(): PersistenciaJugador
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
        throw new \Exception("No se puede deserializar el singletonJugador");
    }

    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();
            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {
            echo "Error de conexión en PersistenciaJugador: " . $e->getMessage();
        }
    }
}
?>