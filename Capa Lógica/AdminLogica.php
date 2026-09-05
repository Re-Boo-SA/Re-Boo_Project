<?php

require_once(__DIR__ . '/IAdminLogica.php');
require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');

class AdminLogica implements IAdminLogica
{
    private FachadaPersistencia $fachadaPersistencia;

    public function __construct()
    {
        $this->fachadaPersistencia = new FachadaPersistencia();
    }

    public function obtenerAdmin(int $idUsuario): ?Administrador
    {
        $persistenciaAdmin = $this->fachadaPersistencia->retornoIPersistenciaAdmin();
        return $persistenciaAdmin->buscarAdmin($idUsuario);
    }

    public function listarAdmins(): array
    {
        $persistenciaAdmin = $this->fachadaPersistencia->retornoIPersistenciaAdmin();
        return $persistenciaAdmin->listarAdmins();
    }
}
?>