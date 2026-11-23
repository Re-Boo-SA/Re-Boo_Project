<?php
interface ITorneoLogica
{
    public function crear(int $idUsuario, string $nombre, int $tamano, int $minutos): array;
    public function inscribir(int $idUsuario, int $idTorneo): array;
    public function salir(int $idUsuario, int $idTorneo): array;
    public function listar(int $idUsuario): array;
    public function detalle(int $idUsuario, int $idTorneo): array;
    public function avanzar(int $idTorneo): void;
    public function miPartidaPendiente(int $idUsuario): ?array;
    public function torneoDePartida(int $idPartida): ?int;
    public function ganados(int $idUsuario): int;
}
?>
