<?php

require_once(__DIR__ . '/../DTO/UsuarioDTO.php');

interface IUsuarioLogica
{
    public function iniciarSesion(string $identificador, string $password): array;

    public function cargarUsuario(int $idUsuario, string $rol): ?Usuario;
}
?>
