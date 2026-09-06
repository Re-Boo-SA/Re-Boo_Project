<?php

require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/IPersistenciaJugador.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaJugador implements IPersistenciaJugador
{
    private $conn = null;
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
        throw new \Exception("No se puede deserializar el singleton de PersistenciaJugador");
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

    public function altaJugador(Usuario $usuario, Jugador $jugador): bool
    {
        if ($this->conn === null) {
            return false;
        }
        if ($usuario !== null || $jugador !== null) {
            try {
                $this->conn->beginTransaction(); // Usa transacciones porque se realizan dos INSERTS y si uno falla no queda un fantasma (sinedo fantasma una INSERT bien hecho y el otro no por lo que dio error, salió y quedo eso ahí)

                $sqlUsuario = "INSERT INTO USUARIOS (Correo, Contra, NombreUsuario, Rol, BajaLogica) 
                           VALUES (:correo, :contra, :nombreUsuario, 'jugador', 0)";
                $stmtUsuario = $this->conn->prepare($sqlUsuario);
                $stmtUsuario->execute([
                    ':correo' => $usuario->getCorreo(),
                    ':contra' => $usuario->getContra(),
                    ':nombreUsuario' => $usuario->getNombreUsuario()
                ]);

                $idGenerado = (int) $this->conn->lastInsertId();
                $usuario->setIdUsuario($idGenerado);
                $jugador->setIdUsuario($idGenerado);

                $sqlJugador = "INSERT INTO JUGADORES (IDUsuario, FichasActuales, CantidadFichas, PntsPartida, PartidasJugadas, PartidasGanadas, BajaLogica) 
                           VALUES (:idUsuario, :fichasActuales, :cantidadFichas, :pntsPartida, :partidasJugadas, :partidasGanadas, 0)";
                $stmtJugador = $this->conn->prepare($sqlJugador);
                $stmtJugador->execute([
                    ':idUsuario' => $idGenerado,
                    ':fichasActuales' => $jugador->getFichasActuales(),
                    ':cantidadFichas' => $jugador->getCantidadFichas(),
                    ':pntsPartida' => $jugador->getPntsPartida(),
                    ':partidasJugadas' => $jugador->getPartidasJugadas(),
                    ':partidasGanadas' => $jugador->getPartidasGanadas()
                ]);

                $this->conn->commit();
                return true;
            } catch (\Exception $e) {
                if ($this->conn->inTransaction()) {
                    $this->conn->rollBack();
                }
                print ("Error al dar de alta jugador completo: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    public function buscarJugador(int $idUsuario): ?Jugador
    {
        if ($this->conn === null) {
            return null;
        }

        $sql = "CALL buscarJugador(?)";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idUsuario]);
            $reader = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($reader) {
                return new Jugador(
                    (int) $reader['IDUsuario'],
                    $reader['Correo'],
                    $reader['Contra'],
                    $reader['NombreUsuario'],
                    (int) $reader['FichasActuales'],
                    (int) $reader['CantidadFichas'],
                    (int) $reader['PntsPartida'],
                    (int) $reader['PartidasJugadas'],
                    (int) $reader['PartidasGanadas'],
                    (bool) $reader['BajaLogica']
                );
            }
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            print ("Error al buscar jugador: " . $e->getMessage());
        }

        return null;
    }


    public function listarJugadores(): array
    {
        $jugadores = [];
        if ($this->conn === null) {
            return $jugadores;
        }

        $sql = "CALL listarJugadores()";

        try {
            $stmt = $this->conn->query($sql);
            while ($reader = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $jugadores[] = new Jugador(
                    (int) $reader['IDUsuario'],
                    $reader['Correo'],
                    '',
                    $reader['NombreUsuario'],
                    (int) $reader['FichasActuales'],
                    (int) $reader['CantidadFichas'],
                    (int) $reader['PntsPartida'],
                    (int) $reader['PartidasJugadas'],
                    (int) $reader['PartidasGanadas'],
                    (bool) $reader['BajaLogica']
                );
            }
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            print ("Error al listar jugadores: " . $e->getMessage());
        }

        return $jugadores;
    }

    public function bajaLogicaJugador(int $idUsuario): bool
    {
        if ($this->conn === null) {
            return false;
        }

        $sql = "CALL bajaJugador(?)";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idUsuario]);
            $stmt->closeCursor();
            return true;
        } catch (\PDOException $e) {
            print ("Error al dar de baja jugador: " . $e->getMessage());
            return false;
        }
    }

    public function modificarJugador(Usuario $usuario, Jugador $jugador): bool // Si es una se declara solo DTO Jugador y si son las dos es como esta (con DTO Usuario y Jugador)
    {     
        if ($this->conn === null) {
            return false;
        }
        if ($usuario !== null || $jugador !== null) {

            try {
                /* Depende de si se modifican las dos o solo una, 
                si son las dos esto es una Transaction 
                y si es una esto es solo de modificar JUGADOR sin Transaction 
                */
                return true;
            } catch (\PDOException $e) {
                return false;
            }
        }
        return false;
    }
}
?>
