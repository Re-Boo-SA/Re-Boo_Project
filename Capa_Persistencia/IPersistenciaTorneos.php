<?php
interface IPersistenciaTorneos
{
    public function iniciarTransaccion(): void;
    public function confirmar(): void;
    public function revertir(): void;
    public function crear(string $nombre, int $tamano, int $idCreador, int $minutos): int;
    public function activosCreadosPor(int $idUsuario): int;
    public function torneo(int $idTorneo, bool $bloquear = false): ?array;
    public function listar(): array;
    public function inscriptos(int $idTorneo): array;
    public function inscribir(int $idTorneo, int $idUsuario): void;
    public function desinscribir(int $idTorneo, int $idUsuario): void;
    public function fijarSemilla(int $idTorneo, int $idUsuario, int $semilla): void;
    public function eliminar(int $idTorneo, int $idUsuario): void;
    public function cambiarEstado(int $idTorneo, string $estado, int $ronda, ?int $idGanador): void;
    public function llaves(int $idTorneo): array;
    public function crearLlave(int $idTorneo, int $ronda, int $posicion, int $a, ?int $b, ?int $idPartida, ?int $ganador, ?string $resultado): void;
    public function resolverLlave(int $idTorneo, int $ronda, int $posicion, int $ganador, string $resultado): void;
    public function resultadoPartida(int $idPartida): ?array;
    public function torneosActivosDe(int $idUsuario): array;
    public function torneoDePartida(int $idPartida): ?int;
    public function ganados(int $idUsuario): int;
}
?>
