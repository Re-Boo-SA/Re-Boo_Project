<?php
interface IPersistenciaRanking
{
    public function top(string $orden, int $limite): array;
    public function posicionDe(string $orden, int $idUsuario): ?array;
}
?>
