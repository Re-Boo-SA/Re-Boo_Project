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
            error_log("Error de conexión en PersistenciaUsuario: " . $e->getMessage());
        }
    }

    public function buscarPorIdentificador(string $identificador): ?Usuario
    {
        if ($this->conn === null) {
            return null;
        }

        $sql = "SELECT IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica 
                FROM USUARIOS 
                WHERE (Correo = :correo OR NombreUsuario = :nombre) AND BajaLogica = 0
                LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':correo' => $identificador, ':nombre' => $identificador]);
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
            error_log("Error al buscar usuario por identificador: " . $e->getMessage());
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
            error_log("Error al verificar existencia de correo: " . $e->getMessage());
            return false;
        }
    }

    public function existeNombreUsuario(string $nombreUsuario, int $excluirId = 0): bool
    {
        if ($this->conn === null) {
            return false;
        }

        // La collation de la tabla es insensible a mayúsculas y tildes: "Pepe" y "pépe" chocan.
        $sql = "SELECT COUNT(*) FROM USUARIOS WHERE NombreUsuario = ? AND IDUsuario <> ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$nombreUsuario, $excluirId]);
            return ((int) $stmt->fetchColumn()) > 0;
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            error_log("Error al verificar existencia de nombre de usuario: " . $e->getMessage());
            return false;
        }
    }

    public function buscarPorId(int $idUsuario): ?Usuario
    {
        if ($this->conn === null) {
            return null;
        }

        $sql = "SELECT IDUsuario, Correo, Contra, NombreUsuario, Rol, BajaLogica
                FROM USUARIOS
                WHERE IDUsuario = ?
                LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idUsuario]);
            $reader = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

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
        } catch (\PDOException $e) {
            error_log("Error al buscar usuario por id: " . $e->getMessage());
        }

        return null;
    }

    public function actualizarContra(int $idUsuario, string $contraHash): bool
    {
        if ($this->conn === null) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare("UPDATE USUARIOS SET Contra = ? WHERE IDUsuario = ?");
            return $stmt->execute([$contraHash, $idUsuario]);
        } catch (\PDOException $e) {
            error_log("Error al actualizar la contraseña: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerDosFactores(int $idUsuario): array
    {
        $vacio = ['secreto' => null, 'codigos' => null];
        if ($this->conn === null) {
            return $vacio;
        }

        try {
            $stmt = $this->conn->prepare("SELECT SecretoTOTP, CodigosRecuperacion FROM USUARIOS WHERE IDUsuario = ? LIMIT 1");
            $stmt->execute([$idUsuario]);
            $reader = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            if ($reader) {
                return ['secreto' => $reader['SecretoTOTP'], 'codigos' => $reader['CodigosRecuperacion']];
            }
        } catch (\PDOException $e) {
            error_log("Error al leer la configuración de 2FA: " . $e->getMessage());
        }

        return $vacio;
    }

    public function guardarDosFactores(int $idUsuario, ?string $secretoCifrado, ?string $codigosRecuperacion): bool
    {
        if ($this->conn === null) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare("UPDATE USUARIOS SET SecretoTOTP = ?, CodigosRecuperacion = ? WHERE IDUsuario = ?");
            return $stmt->execute([$secretoCifrado, $codigosRecuperacion, $idUsuario]);
        } catch (\PDOException $e) {
            error_log("Error al guardar la configuración de 2FA: " . $e->getMessage());
            return false;
        }
    }
}
?>
