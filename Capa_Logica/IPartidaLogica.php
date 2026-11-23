<?php
interface IPartidaLogica
{
    public function crearPartida(int $idUsuario, string $nombre, string $tipo, string $password, int $maxJugadores, string $modo, array $reglas = []): array;
    public function listarSalas(): array;
    public function partidaActiva(int $idUsuario): ?int;
    public function unirse(int $idUsuario, int $idPartida, string $password, bool $porInvitacion = false): array;
    public function crearCompetitiva(array $idsUsuarios): int;
    public function historial(int $idUsuario, int $limite = 10): array;
    public function accion(int $idPartida, int $idUsuario, string $accion, array $datos): array;
    public function vista(int $idPartida, int $idUsuario, int $versionCliente = -1): array;
    public function vistaEspectador(int $idPartida, int $idUsuario, int $versionCliente = -1): array;
    public function crearDeTorneo(array $idsUsuarios, string $nombre): int;
    public function crearDesafio(int $idUsuario, int $idBot, ConfigPartida $config, string $nombre, array $escenario = []): int;
}
?>
