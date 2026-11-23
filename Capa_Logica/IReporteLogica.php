<?php
interface IReporteLogica
{
    /** RF27: un jugador manda un reporte de bug o denuncia a otro jugador. */
    public function enviar(int $idUsuario, string $tipo, string $motivo, string $descripcion, string $denunciado, ?int $idPartida): array;

    public function misReportes(int $idUsuario): array;

    /** RF26: reportes para el panel de administración. */
    public function listarParaAdmin(string $estado): array;

    public function contarPorEstado(): array;

    public function cerrar(int $idAdmin, int $idReporte, string $estado): array;
}
?>
