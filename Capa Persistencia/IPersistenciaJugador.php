<?php

require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');

interface IPersistenciaJugador
{
    public function altaJugador(Usuario $usuario, Jugador $jugador): bool;
    public function buscarJugador(int $idUsuario): ?Jugador;
    public function listarJugadores(): array;

    public function bajaLogicaJugador(int $idUsuario): bool;
}
?>