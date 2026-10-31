<?php

require_once(__DIR__ . '/../config.php');

class ConexionBD
{

    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
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

    public function connect()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $opciones = [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci',
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $opciones);
        } catch (PDOException $e) {
            error_log('Error al conectar a la base de datos: ' . $e->getMessage());
            throw new Exception('No se pudo conectar a la base de datos.');
        }

        return $this->conn;
    }

    public function cerrarRecursos()
    {
        $this->conn = null;
    }
}
?>
