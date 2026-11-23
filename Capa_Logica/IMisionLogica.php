<?php
interface IMisionLogica
{
    public function estado(int $idUsuario): array;
    public function hablar(int $idUsuario, string $idContacto): array;
    public function aceptar(int $idUsuario, string $idMision): array;
    public function charlar(int $idUsuario, string $idContacto, string $idNodo, int $opcion): array;
    public function desafiar(int $idUsuario, string $idMision): array;
    public function esDesafio(int $idPartida): bool;
    public function capituloDeDesafio(int $idPartida): ?string;
}
?>
