<?php

interface ISkinsLogica
{
    public function listarCatalogo(): array;
    public function listarInventario(int $idUsuario): array;
    public function comprarSkin(int $idUsuario, int $idSkin, int $idFicha): array;
}
