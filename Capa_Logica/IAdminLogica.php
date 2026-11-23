<?php

require_once(__DIR__ . '/../DTO/AdministradorDTO.php');

interface IAdminLogica
{
    public function obtenerAdmin(int $idUsuario): ?Administrador;
    public function listarAdmins(): array;
}
?>