<?php

require_once(__DIR__ . '/../config.php');

class ConexionBD
{

    private $host = 'localhost';
    private $db_name = 'proyecto2026';
    private $username = 'root';
    private $password = 'root';
    private $conn;

    public function connect()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $opciones = [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci'
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $opciones);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Lanza una excepción en lugar de solo mostrar el error.
            // Esto detiene la ejecución de forma controlada y avisa a las otras clases.
            throw new Exception("Error al conectar a la base de datos: " . $e->getMessage());
        }

        return $this->conn;
    }

    public function cerrarRecursos()
    {
        $this->conn = null;
    }
}
?>