<?php
require_once('IPersistenciaCampana.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/* Progreso y decisiones de la campaña (etapa D). */
class PersistenciaCampana implements IPersistenciaCampana
{
    private ?PDO $conn = null;
    private static ?PersistenciaCampana $instancia = null;

    public static function getInstancia(): PersistenciaCampana
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
        throw new \Exception("No se puede deserializar el singletonCampana");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaCampana: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('La campaña no está disponible por un problema de conexión.');
        }
        return $this->conn;
    }

    /** [idCapitulo => ['estado' => ..., 'punto' => ...]] */
    public function progreso(int $idUsuario): array
    {
        $s = $this->db()->prepare('SELECT IDCapitulo, Estado, PuntoGuardado FROM CAMPANA_PROGRESO WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        $r = [];
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $r[$f['IDCapitulo']] = ['estado' => $f['Estado'], 'punto' => $f['PuntoGuardado']];
        }
        return $r;
    }

    /** Empieza (o reinicia la cuenta de tiempo si vuelve a empezar de cero). No toca un capítulo completado. */
    public function empezar(int $idUsuario, string $idCapitulo): void
    {
        $this->db()->prepare("INSERT INTO CAMPANA_PROGRESO (IDUsuario, IDCapitulo) VALUES (:u, :c)
            ON DUPLICATE KEY UPDATE FechaInicio = IF(Estado = 'en_curso' AND PuntoGuardado IS NULL, NOW(), FechaInicio)")
            ->execute([':u' => $idUsuario, ':c' => $idCapitulo]);
    }

    public function guardarPunto(int $idUsuario, string $idCapitulo, string $punto): void
    {
        $this->db()->prepare("UPDATE CAMPANA_PROGRESO SET PuntoGuardado = :p WHERE IDUsuario = :u AND IDCapitulo = :c AND Estado = 'en_curso'")
            ->execute([':p' => $punto, ':u' => $idUsuario, ':c' => $idCapitulo]);
    }

    /** true solo la primera vez (así el premio se paga una vez). */
    public function completar(int $idUsuario, string $idCapitulo): bool
    {
        $s = $this->db()->prepare("UPDATE CAMPANA_PROGRESO SET Estado = 'completado', FechaFin = NOW() WHERE IDUsuario = :u AND IDCapitulo = :c AND Estado = 'en_curso'");
        $s->execute([':u' => $idUsuario, ':c' => $idCapitulo]);
        return $s->rowCount() === 1;
    }

    public function segundosDesdeInicio(int $idUsuario, string $idCapitulo): ?int
    {
        $s = $this->db()->prepare('SELECT TIMESTAMPDIFF(SECOND, FechaInicio, NOW()) FROM CAMPANA_PROGRESO WHERE IDUsuario = :u AND IDCapitulo = :c');
        $s->execute([':u' => $idUsuario, ':c' => $idCapitulo]);
        $v = $s->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    public function decisiones(int $idUsuario): array
    {
        $s = $this->db()->prepare('SELECT Clave, Valor FROM CAMPANA_DECISIONES WHERE IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        return array_column($s->fetchAll(PDO::FETCH_ASSOC), 'Valor', 'Clave');
    }

    /* ---------- combate táctico ---------- */

    public function combate(int $idUsuario, string $idCapitulo, string $idCombate): ?array
    {
        $s = $this->db()->prepare('SELECT Estado, Resultado, Intentos FROM CAMPANA_COMBATES WHERE IDUsuario = :u AND IDCapitulo = :c AND IDCombate = :k');
        $s->execute([':u' => $idUsuario, ':c' => $idCapitulo, ':k' => $idCombate]);
        $f = $s->fetch(PDO::FETCH_ASSOC);
        return $f === false ? null : ['estado' => json_decode($f['Estado'], true), 'resultado' => $f['Resultado'], 'intentos' => (int) $f['Intentos']];
    }

    /** Guarda el estado. Con $nuevoIntento, empieza de cero (suma un intento) aunque ya existiera. Una victoria no se pisa con un intento nuevo. */
    public function guardarCombate(int $idUsuario, string $idCapitulo, string $idCombate, string $estado, ?string $resultado, bool $nuevoIntento): void
    {
        if ($nuevoIntento) {
            $this->db()->prepare("INSERT INTO CAMPANA_COMBATES (IDUsuario, IDCapitulo, IDCombate, Estado, Resultado) VALUES (:u, :c, :k, :e, NULL)
                ON DUPLICATE KEY UPDATE Estado = VALUES(Estado), Intentos = Intentos + 1, Resultado = IF(Resultado = 'victoria', 'victoria', NULL)")
                ->execute([':u' => $idUsuario, ':c' => $idCapitulo, ':k' => $idCombate, ':e' => $estado]);
            return;
        }
        $this->db()->prepare("UPDATE CAMPANA_COMBATES SET Estado = :e, Resultado = IF(Resultado = 'victoria', 'victoria', :r)
            WHERE IDUsuario = :u AND IDCapitulo = :c AND IDCombate = :k")
            ->execute([':e' => $estado, ':r' => $resultado, ':u' => $idUsuario, ':c' => $idCapitulo, ':k' => $idCombate]);
    }

    public function decidir(int $idUsuario, string $clave, string $valor): void
    {
        $this->db()->prepare('INSERT INTO CAMPANA_DECISIONES (IDUsuario, Clave, Valor) VALUES (:u, :k, :v) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor), Fecha = NOW()')
            ->execute([':u' => $idUsuario, ':k' => $clave, ':v' => $valor]);
    }
}
?>
