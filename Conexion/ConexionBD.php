<?php

require_once(__DIR__ . '/../config.php');

class ConexionBD
{

    private $host = BD_Host;
    private $port = BD_Puerto;
    private $db_name = BD_Nombre;
    private $username = BD_Usuario;
    private $password = BD_Contra;
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

            // Parche automático para crear las columnas Biografia y Estado si no existen
            try {
                $this->conn->exec("ALTER TABLE JUGADORES ADD Biografia TEXT NULL;");
            } catch (PDOException $e) {
                // Si la columna ya existe, ignora el error
            }

            try {
                $this->conn->exec("ALTER TABLE JUGADORES ADD Estado VARCHAR(20) DEFAULT 'ocupado';");
            } catch (PDOException $e) {
                // Si la columna ya existe, ignora el error
            }

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