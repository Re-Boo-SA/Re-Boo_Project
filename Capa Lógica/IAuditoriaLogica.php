<?php
interface IAuditoriaLogica
{
    public function registrar(string $tipo, string $nivel = 'info', ?int $idUsuario = null, ?string $identificador = null, ?string $detalle = null): void;
    public function contar(string $tipo, int $minutos, bool $porIp = true, ?string $identificador = null, ?int $idUsuario = null): int;
    public function limiteSuperado(string $tipo, int $maximo, int $minutos, ?int $idUsuario = null): bool;
    public function listar(int $limite = 200, string $tipo = ''): array;
    public function sistemaDisponible(): bool;
}
?>
