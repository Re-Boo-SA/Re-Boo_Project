<?php

interface IPersistenciaSkins
{
    public function listarCatalogo(): array;
    public function listarInventario(int $idUsuario): array;
    public function comprarSkin(int $idUsuario, int $idSkin, int $idFicha): array;
    public function listarEquipadas(int $idUsuario): array;
    public function equipar(int $idUsuario, int $idFicha, int $idSkin): bool;
    public function desequipar(int $idUsuario, int $idFicha): void;
}
?>