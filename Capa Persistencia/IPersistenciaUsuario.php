<?php

require_once(__DIR__ . '/../DTO/UsuarioDTO.php');

interface IPersistenciaUsuario
{
    public function buscarPorIdentificador(string $identificador): ?Usuario;
    public function existeCorreo(string $correo): bool;
    public function existeNombreUsuario(string $nombreUsuario): bool;
}
?>