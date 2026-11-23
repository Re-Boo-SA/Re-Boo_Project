<?php
interface IPersistenciaSucesos
{
    public function cobrarPremio(int $idUsuario, int $tramo): bool;
    public function figuraDelDia(): ?array;
    public function ultimoCampeon(): ?array;
}
?>
