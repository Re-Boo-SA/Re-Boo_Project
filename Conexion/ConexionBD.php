<?php

/**
 * Clase ConexionBD
 * Maneja la conexión a la base de datos MySQL mediante PDO (PHP Data Objects).
 * Implementa patrón de diseño con método estático para patrón Singleton opcional,
 * manejo de excepciones y utilidades para transacciones y consultas preparadas.
 */
class ConexionBD
{
    private static ?ConexionBD $instancia = null;
    private ?PDO $conn = null;

    // Configuración de la base de datos
    private string $host;
    private string $port;
    private string $db_name;
    private string $username;
    private string $password;
    private string $charset;

    /**
     * Constructor de la clase ConexionBD.
     * Permite personalizar los parámetros de conexión o utilizar valores por defecto / variables de entorno.
     */
    public function __construct(
        string $host = 'localhost',
        string $db_name = 'TesteoPHP',
        string $username = 'root',
        string $password = 'root',
        string $port = '3306',
        string $charset = 'utf8mb4'
    ) {
        $this->host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : $host;
        $this->db_name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : $db_name;
        $this->username = getenv('DB_USER') !== false ? getenv('DB_USER') : $username;
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : $password;
        $this->port = getenv('DB_PORT') !== false ? getenv('DB_PORT') : $port;
        $this->charset = getenv('DB_CHARSET') !== false ? getenv('DB_CHARSET') : $charset;
    }

    /**
     * Obtiene la instancia Singleton de ConexionBD.
     */
    public static function getInstance(): ConexionBD
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Establece u obtiene la conexión PDO a la base de datos.
     * 
     * @return PDO Instancia activa de PDO
     * @throws Exception Si ocurre un error al conectar
     */
    public function connect(): PDO
    {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
                
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                throw new Exception("Error al conectar a la base de datos: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return $this->conn;
    }

    /**
     * Alias para connect().
     */
    public function getConexion(): PDO
    {
        return $this->connect();
    }

    /**
     * Verifica si existe una conexión activa.
     */
    public function estaConectado(): bool
    {
        return $this->conn !== null;
    }

    /**
     * Cierra la conexión PDO activa.
     */
    public function cerrarRecursos(): void
    {
        $this->conn = null;
    }

    /**
     * Alias para cerrarRecursos().
     */
    public function cerrarConexion(): void
    {
        $this->cerrarRecursos();
    }

    /**
     * Prepara y ejecuta una consulta SQL con parámetros opcionales.
     * 
     * @param string $sql Sentencia SQL con marcadores de posición (?) o nombrados (:param)
     * @param array $params Parámetros para la sentencia preparada
     * @return PDOStatement Sentencia ejecutada
     */
    public function ejecutarConsulta(string $sql, array $params = []): PDOStatement
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Inicia una transacción PDO.
     */
    public function iniciarTransaccion(): bool
    {
        return $this->connect()->beginTransaction();
    }

    /**
     * Confirma una transacción PDO activa.
     */
    public function confirmarTransaccion(): bool
    {
        if ($this->estaConectado()) {
            return $this->conn->commit();
        }
        return false;
    }

    /**
     * Revierte una transacción PDO activa.
     */
    public function cancelarTransaccion(): bool
    {
        if ($this->estaConectado()) {
            return $this->conn->rollBack();
        }
        return false;
    }

    /**
     * Obtiene el último ID insertado en la base de datos.
     */
    public function ultimoIdInsertado(?string $name = null): string|false
    {
        return $this->connect()->lastInsertId($name);
    }
}