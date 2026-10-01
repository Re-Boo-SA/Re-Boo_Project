<?php

require_once(__DIR__ . '/ISkinsLogica.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');

class SkinsLogica implements ISkinsLogica
{
    private FachadaPersistencia $fachada;

    public function __construct()
    {
        $this->fachada = new FachadaPersistencia();
    }

    public function listarCatalogo(): array
    {
        return $this->fachada->retornoIPersistenciaSkins()->listarCatalogo();
    }

    public function listarInventario(int $idUsuario): array
    {
        return $this->fachada->retornoIPersistenciaSkins()->listarInventario($idUsuario);
    }

    // Este metodo verifica los puntos y evita comprar la misma skin dos veces.
    public function comprarSkin(int $idUsuario, int $idSkin, int $idFicha): array
    {
        return $this->fachada->retornoIPersistenciaSkins()->comprarSkin($idUsuario, $idSkin, $idFicha);
    }

}
