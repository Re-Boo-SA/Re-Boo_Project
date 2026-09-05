<?php

interface IUsuarioLogica
{
    public function iniciarSesion(string $identificador, string $password): array;
}
?>