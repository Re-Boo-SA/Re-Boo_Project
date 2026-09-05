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

    private function __wakeup()
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

        try {
            $this->conn->beginTransaction();

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

            $sqlJugador = "INSERT INTO JUGADORES
                (IDUsuario, FichasActuales, CantidadFichas, PntsPartida, PartidasJugadas, PartidasGanadas, BajaLogica)
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
            return false;
        }
    }

    public function buscarJugador(int $idUsuario): ?Jugador
    {
        if ($this->conn === null) {
            return null;
        }

        $sql = "SELECT u.IDUsuario, u.Correo, u.Contra, u.NombreUsuario,
                       j.FichasActuales, j.CantidadFichas, j.PntsPartida,
                       j.PartidasJugadas, j.PartidasGanadas, j.BajaLogica
                FROM JUGADORES j
                INNER JOIN USUARIOS u ON j.IDUsuario = u.IDUsuario
                WHERE j.IDUsuario = :idUsuario
                  AND j.BajaLogica = 0
                  AND u.BajaLogica = 0
                LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':idUsuario' => $idUsuario]);
            $reader = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$reader) {
                return null;
            }

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
        } catch (\PDOException $e) {
            return null;
        }
    }

    public function listarJugadores(): array
    {
        if ($this->conn === null) {
            return [];
        }

        $sql = "SELECT u.IDUsuario, u.Correo, u.Contra, u.NombreUsuario,
                       j.FichasActuales, j.CantidadFichas, j.PntsPartida,
                       j.PartidasJugadas, j.PartidasGanadas, j.BajaLogica
                FROM JUGADORES j
                INNER JOIN USUARIOS u ON j.IDUsuario = u.IDUsuario
                WHERE j.BajaLogica = 0 AND u.BajaLogica = 0";

        try {
            $stmt = $this->conn->query($sql);
            $jugadores = [];

            while ($reader = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $jugadores[] = new Jugador(
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
            return $jugadores;
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function bajaLogicaJugador(int $idUsuario): bool
    {
        if ($this->conn === null) {
            return false;
        }

        $sql = "UPDATE JUGADORES SET BajaLogica = 1 WHERE IDUsuario = :idUsuario";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':idUsuario' => $idUsuario]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function modificarJugador(Usuario $usuario, Jugador $jugador): bool
    {
        if ($this->conn === null) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $sqlUsuario = "UPDATE USUARIOS
                           SET Correo = :correo,
                               NombreUsuario = :nombreUsuario,
                               Contra = :contra
                           WHERE IDUsuario = :idUsuario";
            $stmtUsuario = $this->conn->prepare($sqlUsuario);
            $stmtUsuario->execute([
                ':correo' => $usuario->getCorreo(),
                ':nombreUsuario' => $usuario->getNombreUsuario(),
                ':contra' => $usuario->getContra(),
                ':idUsuario' => $usuario->getIdUsuario()
            ]);

            $sqlJugador = "UPDATE JUGADORES
                           SET FichasActuales = :fichasActuales,
                               CantidadFichas = :cantidadFichas,
                               PntsPartida = :pntsPartida,
                               PartidasJugadas = :partidasJugadas,
                               PartidasGanadas = :partidasGanadas,
                               BajaLogica = :bajaLogica
                           WHERE IDUsuario = :idUsuario";
            $stmtJugador = $this->conn->prepare($sqlJugador);
            $stmtJugador->execute([
                ':fichasActuales' => $jugador->getFichasActuales(),
                ':cantidadFichas' => $jugador->getCantidadFichas(),
                ':pntsPartida' => $jugador->getPntsPartida(),
                ':partidasJugadas' => $jugador->getPartidasJugadas(),
                ':partidasGanadas' => $jugador->getPartidasGanadas(),
                ':bajaLogica' => $jugador->getBajaLogica(),
                ':idUsuario' => $jugador->getIdUsuario()
            ]);

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }
}
?>
