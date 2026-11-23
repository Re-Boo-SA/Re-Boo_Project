<?php
require_once(__DIR__ . '/IReporteLogica.php');
require_once(__DIR__ . '/SeguridadLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * Reportes de bugs y denuncias de jugadores (RF27) y su moderación (RF26).
 * Los reportes del chat de Berlín también terminan acá (tipo 'chat').
 */
class ReporteLogica implements IReporteLogica
{
    public const TIPOS = ['bug', 'jugador'];
    public const ESTADOS_CIERRE = ['resuelto', 'descartado'];
    public const MAXIMO_POR_HORA = 5;

    private IPersistenciaReporte $reportes;

    public function __construct()
    {
        $this->reportes = (new FachadaPersistencia())->retornoIPersistenciaReporte();
    }

    public function enviar(int $idUsuario, string $tipo, string $motivo, string $descripcion, string $denunciado, ?int $idPartida): array
    {
        if (!in_array($tipo, self::TIPOS, true)) {
            return self::error('Elegí si es un problema del juego o una denuncia a un jugador.');
        }
        $motivo = SeguridadLogica::normalizarTexto($motivo);
        $descripcion = SeguridadLogica::normalizarTexto($descripcion, true);
        $denunciado = SeguridadLogica::normalizarTexto($denunciado);

        if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 120) {
            return self::error('El asunto tiene que tener entre 5 y 120 caracteres.');
        }
        if (mb_strlen($descripcion) < 10 || mb_strlen($descripcion) > 1000) {
            return self::error('Contanos qué pasó en 10 a 1000 caracteres.');
        }

        $idDenunciado = null;
        if ($tipo === 'jugador') {
            if ($denunciado === '') {
                return self::error('Escribí el nombre del jugador que querés denunciar.');
            }
            $idDenunciado = $this->reportes->idUsuarioPorNombre($denunciado);
            if ($idDenunciado === null) {
                return self::error('No encontramos a un jugador con ese nombre.');
            }
            if ($idDenunciado === $idUsuario) {
                return self::error('No te podés denunciar a vos mismo.');
            }
        }

        if ($idPartida !== null && !$this->reportes->participoEnPartida($idUsuario, $idPartida)) {
            return self::error('Solo podés asociar el reporte a una partida en la que jugaste.');
        }
        if ($tipo === 'jugador' && $idPartida !== null && !$this->reportes->participoEnPartida($idDenunciado, $idPartida)) {
            return self::error('Ese jugador no estuvo en la partida que elegiste.');
        }

        $auditoria = new AuditoriaLogica();
        if ($auditoria->limiteSuperado('reporte_enviado', self::MAXIMO_POR_HORA, 60, $idUsuario)) {
            return self::error('Ya mandaste varios reportes en la última hora. Probá de nuevo más tarde.');
        }

        $id = $this->reportes->crear($tipo, $motivo, $descripcion, $idUsuario, $idDenunciado, $idPartida);
        $auditoria->registrar('reporte_enviado', $tipo === 'jugador' ? 'aviso' : 'info', $idUsuario, null, 'Reporte #' . $id . ' (' . $tipo . ')');
        return ['exito' => true, 'mensaje' => 'Gracias: tu reporte #' . $id . ' le llegó a los administradores.', 'id' => $id];
    }

    public function misReportes(int $idUsuario): array
    {
        return $this->reportes->listarDeUsuario($idUsuario);
    }

    public function listarParaAdmin(string $estado): array
    {
        return $this->reportes->listarParaAdmin($estado);
    }

    public function contarPorEstado(): array
    {
        return $this->reportes->contarPorEstado();
    }

    public function cerrar(int $idAdmin, int $idReporte, string $estado): array
    {
        if (!in_array($estado, self::ESTADOS_CIERRE, true) || $idReporte <= 0) {
            return self::error('Acción inválida.');
        }
        if (!$this->reportes->cerrar($idReporte, $idAdmin, $estado)) {
            return self::error('Ese reporte ya estaba cerrado o no existe.');
        }
        (new AuditoriaLogica())->registrar('reporte_cerrado', 'info', $idAdmin, null, 'Reporte #' . $idReporte . ' → ' . $estado);
        return ['exito' => true, 'mensaje' => 'Reporte #' . $idReporte . ($estado === 'resuelto' ? ' marcado como resuelto.' : ' descartado.')];
    }

    private static function error(string $mensaje): array
    {
        return ['exito' => false, 'mensaje' => $mensaje];
    }
}
?>
