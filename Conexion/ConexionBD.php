
<?php

class ConexionBD
{

    private $host = 'localhost';
    private $db_name = 'PROYECTO2026';
    private $username = 'root';
    private $password = 'root';
    private $conn;

    public function connect()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8";
            $this->conn = new PDO($dsn, $this->username, $this->password);
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