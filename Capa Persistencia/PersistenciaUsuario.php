<?php

require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/IPersistenciaUsuario.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaUsuario implements IPersistenciaUsuario
{
    private $conn = null;
    private static ?PersistenciaUsuario $instancia = null;

    public static function getInstancia(): PersistenciaUsuario
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
    }

    private function __construct()
    {
        try {
            $conexionBD = new ConexionBD();
            $this->conn = $conexionBD->connect();
        } catch (Exception $e) {
            echo ("Error de conexión en PersistenciaUsuario: " . $e->getMessage());
        }
    }

    public function buscarPorIdentificador(string $identificador): ?Usuario
    {
        if ($this->conn === null) {
            return null;
        }

        $sql = "SELECT IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica 
                FROM USUARIOS 
                WHERE (Correo = :identificador OR NombreUsuario = :identificador) AND BajaLogica = 0
                LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':identificador' => $identificador]);
            $reader = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($reader) {
                return new Usuario(
                    (int) $reader['IDUsuario'],
                    $reader['Correo'],
                    $reader['Contra'],
                    $reader['NombreUsuario'],
                    $reader['Rol'],
                    (bool) $reader['BajaLogica']
                );
            }
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            print ("Error al buscar usuario por identificador: " . $e->getMessage());
        }

        return null;
    }

    public function existeCorreo(string $correo): bool
    {
        if ($this->conn === null) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM USUARIOS WHERE Correo = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$correo]);
            return ((int) $stmt->fetchColumn()) > 0;
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            print ("Error al verificar existencia de correo: " . $e->getMessage());
            return false;
        }
    }

    public function existeNombreUsuario(string $nombreUsuario): bool
    {
        if ($this->conn === null) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM USUARIOS WHERE NombreUsuario = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$nombreUsuario]);
            return ((int) $stmt->fetchColumn()) > 0;
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            print ("Error al verificar existencia de nombre de usuario: " . $e->getMessage());
            return false;
        }
    }
}
?>