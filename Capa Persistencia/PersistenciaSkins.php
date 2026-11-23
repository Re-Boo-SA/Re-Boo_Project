<?php

require_once(__DIR__ . '/../DTO/SkinsDTO.php');
require_once('IPersistenciaSkins.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

class PersistenciaSkins implements IPersistenciaSkins
{
    private ?PDO $conn = null;

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
            error_log('Error de conexión en PersistenciaSkins: ' . $e->getMessage());
        }
    }

    public function listarCatalogo(): array
    {
        $sql = "SELECT s.IDSkin, s.IDFicha, s.NombreSkin, s.Precio, s.URL, f.FichaEspecie
                FROM SKINS s
                INNER JOIN FICHAS f ON f.IDFicha = s.IDFicha AND f.BajaLogica = 0
                INNER JOIN tienda_skins ts ON ts.IDSkin = s.IDSkin AND ts.IDFicha = s.IDFicha
                INNER JOIN tiendas t ON t.IDTienda = ts.IDTienda AND t.BajaLogica = 0
                WHERE s.BajaLogica = 0
                ORDER BY s.IDFicha, s.IDSkin";

        try {
            $stmt = $this->obtenerConexion()->query($sql);
            $catalogo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $catalogo;
        } catch (PDOException $e) {
            error_log('Error al listar el catálogo de skins: ' . $e->getMessage());
            throw new RuntimeException('No se pudo cargar el catálogo de la tienda.', 0, $e);
        }
    }

    public function listarInventario(int $idUsuario): array
    {
        $sql = "SELECT js.IDSkin, js.IDFicha, s.NombreSkin, s.Precio, s.URL, f.FichaEspecie
                FROM jugador_skins js
                INNER JOIN SKINS s ON s.IDSkin = js.IDSkin AND s.IDFicha = js.IDFicha
                INNER JOIN FICHAS f ON f.IDFicha = js.IDFicha
                WHERE js.IDUsuario = :idUsuario
                ORDER BY js.IDFicha, js.IDSkin";

        try {
            $stmt = $this->obtenerConexion()->prepare($sql);
            $stmt->execute([':idUsuario' => $idUsuario]);
            $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $inventario;
        } catch (PDOException $e) {
            error_log('Error al listar el inventario de skins: ' . $e->getMessage());
            if ($e->getCode() === '42S02' || (int) ($e->errorInfo[1] ?? 0) === 1146) {
                throw new RuntimeException(
                    'La base de datos necesita la migración de la tienda. Ejecuta sql/migracion_tienda_skins.sql y vuelve a intentarlo.',
                    0,
                    $e
                );
            }
            throw new RuntimeException('No se pudo cargar tu colección de skins.', 0, $e);
        }
    }

    public function comprarSkin(int $idUsuario, int $idSkin, int $idFicha): array
    {
        if ($idUsuario < 1 || $idSkin < 1 || $idFicha < 1) {
            return ['exito' => false, 'mensaje' => 'La skin seleccionada no es válida.'];
        }

        try {
            $conexion = $this->obtenerConexion();
            $conexion->beginTransaction();

            $stmtSkin = $conexion->prepare(
                "SELECT s.Precio
                 FROM SKINS s
                 INNER JOIN FICHAS f ON f.IDFicha = s.IDFicha AND f.BajaLogica = 0
                 INNER JOIN tienda_skins ts ON ts.IDSkin = s.IDSkin AND ts.IDFicha = s.IDFicha
                 INNER JOIN tiendas t ON t.IDTienda = ts.IDTienda AND t.BajaLogica = 0
                 WHERE s.IDSkin = :idSkin AND s.IDFicha = :idFicha AND s.BajaLogica = 0
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtSkin->execute([':idSkin' => $idSkin, ':idFicha' => $idFicha]);
            $precio = $stmtSkin->fetchColumn();
            $stmtSkin->closeCursor();

            if ($precio === false) {
                $conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Esta skin ya no está disponible.'];
            }
            $precio = (int) $precio;
            if ($precio < 0) {
                $conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Esta skin no tiene un precio válido.'];
            }

            $stmtJugador = $conexion->prepare(
                'SELECT PntsPartida FROM JUGADORES WHERE IDUsuario = :idUsuario AND BajaLogica = 0 FOR UPDATE'
            );
            $stmtJugador->execute([':idUsuario' => $idUsuario]);
            $puntos = $stmtJugador->fetchColumn();
            $stmtJugador->closeCursor();

            if ($puntos === false) {
                $conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'No se encontró tu cuenta de jugador.'];
            }

            $stmtPosee = $conexion->prepare(
                'SELECT 1 FROM jugador_skins WHERE IDUsuario = :idUsuario AND IDSkin = :idSkin AND IDFicha = :idFicha'
            );
            $stmtPosee->execute([
                ':idUsuario' => $idUsuario,
                ':idSkin' => $idSkin,
                ':idFicha' => $idFicha
            ]);
            $yaPosee = $stmtPosee->fetchColumn() !== false;
            $stmtPosee->closeCursor();

            if ($yaPosee) {
                $conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Ya tienes esta skin en tu colección.'];
            }

            if ((int) $puntos < $precio) {
                $conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'No tienes puntos suficientes para esta compra.'];
            }

            if ($precio > 0) {
                $stmtDescontar = $conexion->prepare(
                    'UPDATE JUGADORES SET PntsPartida = PntsPartida - :precio
                     WHERE IDUsuario = :idUsuario AND PntsPartida >= :precioMinimo'
                );
                $stmtDescontar->execute([
                    ':precio' => $precio,
                    ':idUsuario' => $idUsuario,
                    ':precioMinimo' => $precio
                ]);

                if ($stmtDescontar->rowCount() !== 1) {
                    $conexion->rollBack();
                    return ['exito' => false, 'mensaje' => 'No tienes puntos suficientes para esta compra.'];
                }
            }

            $stmtGuardar = $conexion->prepare(
                'INSERT INTO jugador_skins (IDUsuario, IDSkin, IDFicha)
                 VALUES (:idUsuario, :idSkin, :idFicha)'
            );
            $stmtGuardar->execute([
                ':idUsuario' => $idUsuario,
                ':idSkin' => $idSkin,
                ':idFicha' => $idFicha
            ]);

            $conexion->commit();
            return ['exito' => true, 'mensaje' => 'Skin comprada y agregada a tu colección.'];
        } catch (PDOException $e) {
            if (isset($conexion) && $conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log('Error al comprar una skin: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'No se pudo completar la compra. Inténtalo nuevamente.'];
        }
    }

    private function obtenerConexion(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('La tienda no está disponible por un problema de conexión.');
        }

        return $this->conn;
    }
}