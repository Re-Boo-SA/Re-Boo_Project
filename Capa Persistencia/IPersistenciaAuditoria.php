<?php

interface IPersistenciaAuditoria
{
    public function registrarEvento(string $tipo, string $nivel, ?int $idUsuario, ?string $identificador, string $ip, ?string $detalle): bool;
    public function contarEventos(string $tipo, int $minutos, ?string $ip = null, ?string $identificador = null, ?int $idUsuario = null): int;
    public function listarEventos(int $limite = 200, string $tipo = ''): array;
    public function ping(): bool;
}
?>
