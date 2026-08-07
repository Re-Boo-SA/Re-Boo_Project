<?php

require_once(__DIR__ . '/../DTO/JugadasDTO.php');
require_once('IPersistenciaJugadas.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaJugadas implements IPersistenciaJugadas
{

    private $conn;
    private $res;

    //instancia de clase, el ponerle ? delante de PersistenciaLogo indica que puede ser nulo, es decir que no tiene valor
    private static ?PersistenciaJugadas $instancia = null;
    //metodo para obtener la instancia de la clase
    public static function getInstancia(): PersistenciaJugadas
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }
    //evita que clonen la instancia de clase, es decir que no se pueda crear otra instancia de la clase
    private function __clone()
    {
    }
    //evita que se pueda deserializar la instancia de clase
    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar el singletonJugadas");
    }
    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();

            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {

            echo "Error de conexión en PersistenciaJugadas: " . $e->getMessage();
        }
    }
}
?>