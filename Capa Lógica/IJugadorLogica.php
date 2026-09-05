<?php

require_once(__DIR__ . '/../DTO/JugadorDTO.php');

interface IJugadorLogica
{
    public function registrarJugador(string $nombreUsuario, string $correo, string $password, string $confirmarPassword): array;

    public function obtenerJugador(int $idUsuario): ?Jugador;

    public function listarJugadores(): array;
}
?>