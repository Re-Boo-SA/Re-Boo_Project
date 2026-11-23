<?php

require_once(__DIR__ . '/ISkinsLogica.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');

class SkinsLogica implements ISkinsLogica
{
    private FachadaPersistencia $fachadaPersistencia;

    public function __construct()
    {
        $this->fachadaPersistencia = new FachadaPersistencia();
    }

    public function listarCatalogo(): array
    {
        return $this->fachadaPersistencia->retornoIPersistenciaSkins()->listarCatalogo();
    }

    public function listarInventario(int $idUsuario): array
    {
        return $this->fachadaPersistencia->retornoIPersistenciaSkins()->listarInventario($idUsuario);
    }

    public function comprarSkin(int $idUsuario, int $idSkin, int $idFicha): array
    {
        return $this->fachadaPersistencia->retornoIPersistenciaSkins()->comprarSkin($idUsuario, $idSkin, $idFicha);
    }
}
