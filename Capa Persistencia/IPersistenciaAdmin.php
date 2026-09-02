<?php

interface IPersistenciaAdmin
{
    public function altaAdmin(AdministradorDTO $adminDTO): bool;
    public function buscarAdmin(int $adminID): ?AdministradorDTO;
}
?>