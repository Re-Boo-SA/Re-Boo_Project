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

    public function altaAdmin (AdministradorDTO $adminDTO): bool {
        if ($this->conn != null){
            if ($adminDTO != null){
                $sql="INSERT INTO Administradores (NombreUsuario, Email, Contrasenia) VALUES (?, ?, ?)";
                $NombreUsuario = $adminDTO->getNombreUsuario();
                $Email = $adminDTO->getEmail();
                $Contrasenia = $adminDTO->getContrasenia();
                try {
                    $stmt =$this->conn->prepare($sql);
                    $stmt->execute[$NombreUsuario, $Email, $Contrasenia];
                    $stmt->closeCursor();
                    $res = true;
                } catch (\PDOException $e) {
                    print "Error al dar de alta al admin: " . $e->getMessage();
                    $res = false;
                }
            }
        }
        return $res;
    }

    public function buscarAdmin (int $adminID): ?AdministradorDTO {
        $adminEncontrado = null;
        if ($this->conn != null) {

            $sql = "SELECT * FROM aministradores WHERE AdministradorID = ?";
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$adminID]);

                $reader = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($reader) {
                    $AdministradorID = $reader['Administrador'];
                    $NombreUsuario = $reader['NombreUsuario'];
                    $Email = $reader['Email'];
                    $Contrasenia = $reader['Contrasenia'];

                    $adminEncontrado = new AdministradorDTO($AdministradorID, $NombreUsuario, $Email, $Contrasenia);
                }
                $stmt->closeCursor();
                return $adminEncontrado;
            } catch (\PDOException $e) {
                print "Error al buscar admin: " . $e->getMessage();
                $adminEncontrado = null;
            }
        }
        return $adminEncontrado;
    }
    
}
?>