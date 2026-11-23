<?php

require_once(__DIR__ . '/ISkinsLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

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

    /** [IDFicha => IDSkin] de las skins que el jugador tiene puestas. */
    public function listarEquipadas(int $idUsuario): array
    {
        return $this->fachadaPersistencia->retornoIPersistenciaSkins()->listarEquipadas($idUsuario);
    }

    /** Pone una skin propia en una ficha. $idSkin = 0 vuelve a la apariencia original. */
    public function equipar(int $idUsuario, int $idFicha, int $idSkin): array
    {
        $persistencia = $this->fachadaPersistencia->retornoIPersistenciaSkins();
        if ($idSkin === 0) {
            $persistencia->desequipar($idUsuario, $idFicha);
            return ['exito' => true, 'mensaje' => 'La ficha volvió a su apariencia original.'];
        }
        if (!$persistencia->equipar($idUsuario, $idFicha, $idSkin)) {
            return ['exito' => false, 'mensaje' => 'Solo podés equipar skins que compraste.'];
        }
        return ['exito' => true, 'mensaje' => 'Skin equipada: se va a ver en tus próximas partidas.'];
    }
}
