<?php
require_once(__DIR__ . '/IAuditoriaLogica.php');
require_once(__DIR__ . '/SeguridadLogica.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');

class AuditoriaLogica implements IAuditoriaLogica
{
    private FachadaPersistencia $fachadaPersistencia;

    public function __construct()
    {
        $this->fachadaPersistencia = new FachadaPersistencia();
    }

    public function registrar(string $tipo, string $nivel = 'info', ?int $idUsuario = null, ?string $identificador = null, ?string $detalle = null): void
    {
        if (!in_array($nivel, ['info', 'aviso', 'critico'], true)) {
            $nivel = 'info';
        }

        $ip = SeguridadLogica::ipCliente();

        $this->fachadaPersistencia->retornoIPersistenciaAuditoria()
            ->registrarEvento($tipo, $nivel, $idUsuario, $identificador, $ip, $detalle);

        $linea = sprintf(
            "%s reboo tipo=%s nivel=%s ip=%s usuario=%s identificador=\"%s\" detalle=\"%s\"\n",
            date('c'),
            self::limpiar($tipo),
            $nivel,
            self::limpiar($ip),
            $idUsuario ?? '-',
            self::limpiar($identificador ?? ''),
            self::limpiar($detalle ?? '')
        );

        $carpeta = self::carpetaLogs();
        if (is_dir($carpeta) || @mkdir($carpeta, 0750, true)) {
            @file_put_contents($carpeta . '/seguridad.log', $linea, FILE_APPEND | LOCK_EX);
        }
    }

    public function contar(string $tipo, int $minutos, bool $porIp = true, ?string $identificador = null, ?int $idUsuario = null): int
    {
        return $this->fachadaPersistencia->retornoIPersistenciaAuditoria()->contarEventos(
            $tipo,
            $minutos,
            $porIp ? SeguridadLogica::ipCliente() : null,
            $identificador,
            $idUsuario
        );
    }

    public function limiteSuperado(string $tipo, int $maximo, int $minutos, ?int $idUsuario = null): bool
    {
        if ($idUsuario !== null) {
            return $this->contar($tipo, $minutos, false, null, $idUsuario) >= $maximo;
        }
        return $this->contar($tipo, $minutos, true) >= $maximo;
    }

    public function listar(int $limite = 200, string $tipo = ''): array
    {
        return $this->fachadaPersistencia->retornoIPersistenciaAuditoria()->listarEventos($limite, $tipo);
    }

    public function sistemaDisponible(): bool
    {
        return $this->fachadaPersistencia->retornoIPersistenciaAuditoria()->ping();
    }

    public static function carpetaLogs(): string
    {
        $entorno = getenv('REBOO_CARPETA_LOGS');
        if ($entorno !== false && $entorno !== '') {
            return rtrim($entorno, '/');
        }
        return dirname(__DIR__) . '/logs';
    }

    private static function limpiar(string $valor): string
    {
        $valor = preg_replace('/[\x00-\x1F\x7F"\\\\]/u', '_', $valor) ?? '';
        return mb_substr($valor, 0, 150);
    }
}
?>
