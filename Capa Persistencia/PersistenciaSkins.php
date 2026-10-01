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

    // El catalogo solo enseña skins publicadas en la tienda.
    public function listarCatalogo(): array
    {
        if ($this->conn === null) {
            return [];
        }
        try {
            $stmt = $this->conn->query('CALL listarCatalogoSkins()');
            $catalogo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $catalogo;
        } catch (PDOException $e) {
            return [];
        }
    }

    // Devuelve solo las skins que el jugador ya compro.
    public function listarInventario(int $idUsuario): array
    {
        if ($this->conn === null) {
            return [];
        }
        try {
            // El procedimiento almacenado devuelve el inventario del jugador.
            $stmt = $this->conn->prepare('CALL listarInventarioSkins(?)');
            $stmt->execute([$idUsuario]);
            $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $inventario;
        } catch (PDOException $e) {
            return [];
        }
    }

    // Compra atomica: primero comprueba puntos, luego descuenta y finalmente guarda el inventario.
    public function comprarSkin(int $idUsuario, int $idSkin, int $idFicha): array
    {
        if ($this->conn === null) {
            return ['exito' => false, 'mensaje' => 'No hay conexion con la base de datos.'];
        }
        try {
            // El procedimiento hace la transaccion completa en MySQL.
                $stmt = $this->conn->prepare('CALL comprarSkin(?, ?, ?)');
            $stmt->execute([$idUsuario, $idSkin, $idFicha]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
            return [
                'exito' => (bool) ($resultado['resultado'] ?? false),
                'mensaje' => $resultado['mensaje'] ?? 'No se pudo completar la compra.'
            ];
        } catch (Throwable $e) {
            return ['exito' => false, 'mensaje' => 'No se pudo completar la compra.'];
        }
    }

}
?>