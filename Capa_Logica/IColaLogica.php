<?php
interface IColaLogica
{
    public function entrar(int $idUsuario): array;
    public function salir(int $idUsuario): void;
    public function estado(int $idUsuario): array;
}
?>
