<?php
interface IDosFactoresLogica
{
    public function estaActivo(int $idUsuario): bool;
    public function generarSecreto(): string;
    public function uriConfiguracion(string $secreto, string $nombreCuenta): string;
    public function confirmarActivacion(int $idUsuario, string $secreto, string $codigo): array;
    public function verificarLogin(int $idUsuario, string $codigo): bool;
    public function desactivar(int $idUsuario, string $password, string $codigo): array;
    public function codigosRestantes(int $idUsuario): int;
}
?>
