<?php
interface IMundoLogica
{
    public function mapa(): array;
    public function temporadaVigente(): array;
    public function dominio(int $idTemporada): array;
    public function registrarPartida(int $idPartida, bool $competitiva): void;
    public function elegirBando(int $idUsuario, string $bando): array;
    public function mover(int $idUsuario, int $x, int $y, int $direccion): array;
    public function estadoLiviano(int $idUsuario, int $desdeMensaje = 0): array;
    public function estado(int $idUsuario): array;
    public function gesto(int $idUsuario, int $gesto): array;
    public function guardarApariencia(int $idUsuario, int $piel, int $pelo, int $gorro): array;
    public function decir(int $idUsuario, string $texto): array;
    public function reportar(int $idUsuario, int $idMensaje): array;
    public function salas(int $idDistrito): array;
    public function crearMesa(int $idUsuario, int $idDistrito, int $jugadores, string $nombreUsuario): array;
}
?>
