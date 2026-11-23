<?php
interface IPersistenciaCola
{
    public function iniciarTransaccion(): void;
    public function confirmar(): void;
    public function revertir(): void;
    public function entrar(int $idUsuario): void;
    public function salir(int $idUsuario): void;
    public function latido(int $idUsuario): void;
    public function filaDe(int $idUsuario): ?array;
    public function limpiarInactivos(int $segundos): void;
    public function esperandoBloqueados(): array;
    public function asignar(array $idsUsuarios, int $idPartida): void;
    public function cantidadEsperando(): int;
}
?>
