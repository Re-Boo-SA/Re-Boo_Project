<?php

require_once(__DIR__ . '/../config.php');

class ConexionBD
{

    private $host = 'localhost';
    private $port = '3306';
    private $db_name = 'proyecto2026';
    private $username = 'root';
    private $password = 'root';
    private $conn;

    public function __construct()
    {
        $this->host = self::valorConfig('BD_HOST', 'localhost');
        $this->port = self::valorConfig('BD_PUERTO', '3306');
        $this->db_name = self::valorConfig('BD_NOMBRE', 'proyecto2026');
        $this->username = self::valorConfig('BD_USUARIO', 'reboo_app');
        $this->password = self::valorConfig('BD_CONTRA', '');
    }

    private static function valorConfig(string $nombre, string $defecto): string
    {
        $entorno = getenv('REBOO_' . $nombre);
        if ($entorno !== false && $entorno !== '') {
            return $entorno;
        }
        return defined($nombre) ? (string) constant($nombre) : $defecto;
    }

    /**
     * Todas las persistencias comparten UNA conexión por pedido HTTP.
     * Antes cada una abría la suya (3 conexiones por pedido); con muchos
     * jugadores en el mundo abierto eso saturaba MySQL. Además una misma
     * transacción puede tocar tablas de distintas persistencias.
     */
    private static ?PDO $compartida = null;

    public function connect()
    {
        return self::compartida();
    }

    public static function compartida(): PDO
    {
        if (self::$compartida === null) {
            self::$compartida = (new self())->abrir();
            // Todos los cálculos de tiempo se hacen con el reloj de MySQL (NOW()),
            // así no importa si PHP y MySQL tienen zonas horarias distintas.
        }
        return self::$compartida;
    }

    private function abrir(): PDO
    {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $opciones = [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                // Opcional con Apache: reutiliza la conexión entre pedidos (config.php: BD_PERSISTENTE).
                PDO::ATTR_PERSISTENT => self::valorConfig('BD_PERSISTENTE', '0') === '1',
            ];
            return new PDO($dsn, $this->username, $this->password, $opciones);
        } catch (PDOException $e) {
            error_log('Error al conectar a la base de datos: ' . $e->getMessage());
            throw new Exception('No se pudo conectar a la base de datos.');
        }
    }

    public function cerrarRecursos()
    {
        // La conexión es compartida: se cierra sola al terminar el pedido.
    }
}
?>
