<?php
require_once(__DIR__ . '/../DTO/EstadoPartidaDTO.php');

interface IPersistenciaPartidas
{
    public function iniciarTransaccion(): void;
    public function confirmar(): void;
    public function revertir(): void;
    public function bloquear(int $idPartida): bool;
    public function version(int $idPartida): ?int;
    public function crearPartida(string $nombre, string $tipo, ?string $hashContra, string $modo, int $maxJugadores, int $idCreador, ConfigPartida $config): int;
    public function agregarParticipante(int $idPartida, int $idUsuario, int $asiento): void;
    public function quitarParticipante(int $idPartida, int $idUsuario): void;
    public function partidaActivaDeUsuario(int $idUsuario): ?int;
    public function listarSalas(): array;
    public function datosSala(int $idPartida): ?array;
    public function botsLibres(): array;
    public function cargarEstado(int $idPartida): ?EstadoPartida;
    public function actualizarPartida(int $idPartida, array $campos, array $fechasAhora = [], ?string $cara = null): void;
    public function sumarVersion(int $idPartida): void;
    public function actualizarParticipacion(int $idPartida, int $idUsuario, array $campos): void;
    public function desconfirmarTodos(int $idPartida): void;
    public function reasignarAsientos(int $idPartida, array $orden): void;
    public function latido(int $idPartida, int $idUsuario): void;
    public function latidoTodos(int $idPartida): void;
    public function insertarBolsa(int $idPartida, array $tipos): void;
    public function actualizarFicha(int $idPartida, int $nro, string $ubicacion, ?int $idUsuario, ?string $recinto = null, ?int $posicion = null, ?int $ronda = null, ?int $turno = null): void;
    public function guardarTablero(int $idPartida, array $tablero): void;
    public function pasarFichasDeMano(int $idPartida, array $nuevoDuenoPorNro): void;
    public function insertarJugada(int $idPartida, int $nro, int $idUsuario, string $recinto, ?string $origen, string $tipo, int $ronda, int $turno): void;
    public function borrarColocacion(int $idPartida, int $nro, int $ronda, int $turno): void;
    public function registrarResultado(int $idUsuario, bool $gano, int $puntosTienda, int $puntosRanking, ?int $mmrNuevo = null): void;
    public function historialJugador(int $idUsuario, int $limite = 10): array;
    public function skinsPorJugador(array $idsUsuarios): array;
    public function imagenesFichas(): array;
}
?>
