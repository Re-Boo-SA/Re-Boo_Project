<?php
interface IFichaLogica
{
    public function altaFicha(Ficha $PFicha): bool;
    public function bajaFicha(int $PFichaID): bool;
    public function modificarFicha(Ficha $PFicha): bool;
    public function obtenerFicha(int $PFichaID): ?Ficha;
    public function listarFichas(): array;
}
?>