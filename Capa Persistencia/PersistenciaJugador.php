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


    public function altaJugador(JugadorDTO $jugadorDTO): bool
    {
        if ($this->conn != null) {

            if ($jugadorDTO != null) {
                $sql = "INSERT INTO jugadores (NombreUsuario, Email, Contrasenia, CantidadFichas, PuntosPartida, PartidasJugadas, PartidasGanadas) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $NombreUsuario = $jugadorDTO->getNombreUsuario();
                $Email = $jugadorDTO->getEmail();
                $Contrasenia = $jugadorDTO->getContrasenia();
                $CantidadFichas = $jugadorDTO->getCantidadFichas();
                $PuntosPartida = $jugadorDTO->getPuntosPartida();
                $PartidasJugadas = $jugadorDTO->getPartidasJugadas();
                $PartidasGanadas = $jugadorDTO->getPartidasGanadas();
                try {
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$NombreUsuario, $Email, $Contrasenia, $CantidadFichas, $PuntosPartida, $PartidasJugadas, $PartidasGanadas]);
                    $stmt->closeCursor();
                    $res = true;
                } catch (\PDOException $e) {
                    print "Error al dar de alta jugador: " . $e->getMessage();
                    $res = false;
                }
            }
        }
        return $res;
    }

    public function buscarJugador(int $JugadorID): ?JugadorDTO
    {
        $jugadorEncontrado = null;
        if ($this->conn != null) {

            $sql = "SELECT * FROM jugadores WHERE JugadorID = ?";
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$JugadorID]);

                $reader = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($reader) {
                    $JugadorIDx = $reader['JugadorID'];
                    $NombreUsuario = $reader['NombreUsuario'];
                    $Email = $reader['Email'];
                    $Contrasenia = $reader['Contrasenia'];
                    $CantidadFichas = $reader['CantidadFichas'];
                    $PuntosPartida = $reader['PuntosPartida'];
                    $PartidasJugadas = $reader['PartidasJugadas'];
                    $PartidasGanadas = $reader['PartidasGanadas'];

                    $jugadorEncontrado = new JugadorDTO($JugadorIDx, $NombreUsuario, $Email, $Contrasenia, $CantidadFichas, $PuntosPartida, $PartidasJugadas, $PartidasGanadas);
                }
                $stmt->closeCursor();
                return $jugadorEncontrado;
            } catch (\PDOException $e) {
                print "Error al buscar jugador: " . $e->getMessage();
                $jugadorEncontrado = null;
            }
        }
        return $jugadorEncontrado;
    }
}
?>