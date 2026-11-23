<?php
interface IPersistenciaReporte
{
    /** Guarda un reporte nuevo (pendiente) y devuelve su ID. */
    public function crear(string $tipo, string $motivo, ?string $descripcion, ?int $idReportante, ?int $idDenunciado, ?int $idPartida): int;

    /** Reportes que mandó un usuario, del más nuevo al más viejo. */
    public function listarDeUsuario(int $idUsuario, int $limite = 20): array;

    /** Reportes para el panel de administración, filtrados por estado ('' = todos). */
    public function listarParaAdmin(string $estado, int $limite = 100): array;

    public function contarPorEstado(): array;

    /** Pasa un reporte pendiente a 'resuelto' o 'descartado' y anota qué administrador lo atendió. */
    public function cerrar(int $idReporte, int $idAdmin, string $estado): bool;

    /** ID de un usuario activo por su nombre exacto (sin distinguir mayúsculas), o null. */
    public function idUsuarioPorNombre(string $nombre): ?int;

    public function participoEnPartida(int $idUsuario, int $idPartida): bool;
}
?>
