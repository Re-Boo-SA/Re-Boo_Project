<?php

require_once(__DIR__ . '/../DTO/AdministradorDTO.php');

interface IPersistenciaAdmin
{
    public function buscarAdmin(int $idUsuario): ?Administrador;
    public function listarAdmins(): array;
}
?>