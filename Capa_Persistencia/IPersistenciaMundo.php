<?php
interface IPersistenciaMundo
{
    public function ultimaTemporada(): ?array;
    public function crearTemporada(int $numero): void;
    public function cerrarTemporada(int $idTemporada, string $ganador, int $distritosOeste, int $distritosEste): void;
    public function distritos(): array;
    public function influencia(int $idTemporada): array;
    public function sumarInfluencia(int $idTemporada, int $idDistrito, string $bando, int $puntos): void;
    public function bandoDe(int $idUsuario): ?array;
    public function elegirBando(int $idUsuario, string $bando, int $idTemporada): void;
    public function posicion(int $idUsuario): ?array;
    public function guardarPosicion(int $idUsuario, int $x, int $y, int $direccion): bool;
    public function crearPosicion(int $idUsuario, int $x, int $y, int $direccion): void;
    public function refrescarPerfil(int $idUsuario): void;
    public function purgarPosiciones(): void;
    public function jugadoresActivos(int $segundos): array;
    public function apariencia(int $idUsuario): ?array;
    public function gesto(int $idUsuario, int $gesto, int $segundos): bool;
    public function personaje(int $idUsuario): ?string;
    public function guardarPersonaje(int $idUsuario, string $codigo): void;
    public function guardarApariencia(int $idUsuario, int $piel, int $pelo, int $gorro): void;
    public function mensajesRecientes(int $segundos, int $desde = 0): array;
    public function decir(int $idUsuario, string $texto): void;
    public function mensaje(int $idMensaje): ?array;
    public function purgarMensajes(): void;
    public function reportar(string $motivo): void;
    public function asignarDistrito(int $idPartida, int $idDistrito): void;
    public function distritoDePartida(int $idPartida): ?int;
    public function salasDeDistrito(int $idDistrito): array;
    public function participantesParaInfluencia(int $idPartida): array;
    public function sumarBonoTienda(int $idPartida, int $idUsuario, int $puntos): void;
}
?>
