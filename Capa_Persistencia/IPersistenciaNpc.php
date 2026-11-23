<?php
interface IPersistenciaNpc
{
    public function confianza(int $idUsuario, string $idContacto): ?int;
    public function guardarConfianza(int $idUsuario, string $idContacto, int $valor): void;
    public function recuerdos(int $idUsuario, string $idContacto): array;
    public function recordar(int $idUsuario, string $idContacto, string $clave): bool;
    public function bandoDe(int $idUsuario): ?string;
}
?>
