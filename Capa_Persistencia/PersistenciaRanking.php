<?php
require_once('IPersistenciaRanking.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/*
 * Consultas del ranking global. Los bots y las cuentas dadas de baja no aparecen.
 * Para el % de victorias hace falta un mínimo de partidas (si no, uno que jugó
 * una sola y ganó tendría 100 %).
 */
class PersistenciaRanking implements IPersistenciaRanking
{
    public const MINIMO_PARA_PORCENTAJE = 5;

    private ?PDO $conn = null;
    private static ?PersistenciaRanking $instancia = null;

    public static function getInstancia(): PersistenciaRanking
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
        throw new \Exception("No se puede deserializar el singletonRanking");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaRanking: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('El ranking no está disponible por un problema de conexión.');
        }
        return $this->conn;
    }

    /** Expresión de orden (y desempate) para cada pestaña. Nunca viene del usuario sin validar. */
    private static function orden(string $orden): array
    {
        return match ($orden) {
            'historicos' => ['j.PuntosHistoricos', 'j.PuntosHistoricos DESC, j.PartidasGanadas DESC', '1 = 1'],
            'victorias' => ['j.PartidasGanadas', 'j.PartidasGanadas DESC, j.PuntosHistoricos DESC', '1 = 1'],
            'porcentaje' => [
                '(j.PartidasGanadas / j.PartidasJugadas)',
                '(j.PartidasGanadas / j.PartidasJugadas) DESC, j.PartidasJugadas DESC',
                'j.PartidasJugadas >= ' . self::MINIMO_PARA_PORCENTAJE,
            ],
            default => ['j.MMR', 'j.MMR DESC, j.PartidasCompetitivas DESC', '1 = 1'],
        };
    }

    private const BASE = "FROM JUGADORES j INNER JOIN USUARIOS u ON u.IDUsuario = j.IDUsuario
                          WHERE j.EsBot = 0 AND j.BajaLogica = 0 AND u.BajaLogica = 0";

    private const COLUMNAS = "j.IDUsuario, u.NombreUsuario, j.MMR, j.PuntosHistoricos, j.PartidasJugadas,
                              j.PartidasGanadas, j.PartidasCompetitivas,
                              ROUND(IF(j.PartidasJugadas = 0, 0, 100 * j.PartidasGanadas / j.PartidasJugadas), 1) AS Porcentaje";

    public function top(string $orden, int $limite): array
    {
        [, $ordenar, $filtro] = self::orden($orden);
        $limite = max(1, min(200, $limite));
        $sql = 'SELECT ' . self::COLUMNAS . ' ' . self::BASE . " AND $filtro ORDER BY $ordenar, u.NombreUsuario LIMIT $limite";
        $filas = $this->db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as $i => &$fila) {
            $fila['Puesto'] = $i + 1;
        }
        return $filas;
    }

    public function posicionDe(string $orden, int $idUsuario): ?array
    {
        [$valor, , $filtro] = self::orden($orden);
        $s = $this->db()->prepare('SELECT ' . self::COLUMNAS . ', ' . $valor . ' AS Valor ' . self::BASE . " AND $filtro AND j.IDUsuario = :u");
        $s->execute([':u' => $idUsuario]);
        $fila = $s->fetch(PDO::FETCH_ASSOC);
        if ($fila === false) {
            return null;
        }
        $s = $this->db()->prepare('SELECT COUNT(*) ' . self::BASE . " AND $filtro AND $valor > :v");
        $s->execute([':v' => $fila['Valor']]);
        $fila['Puesto'] = (int) $s->fetchColumn() + 1;
        return $fila;
    }
}
?>
