<?php
interface IPersistenciaCampana
{
    public function progreso(int $idUsuario): array;
    public function empezar(int $idUsuario, string $idCapitulo): void;
    public function guardarPunto(int $idUsuario, string $idCapitulo, string $punto): void;
    public function completar(int $idUsuario, string $idCapitulo): bool;
    public function segundosDesdeInicio(int $idUsuario, string $idCapitulo): ?int;
    public function decisiones(int $idUsuario): array;
    public function decidir(int $idUsuario, string $clave, string $valor): void;
    public function combate(int $idUsuario, string $idCapitulo, string $idCombate): ?array;
    public function guardarCombate(int $idUsuario, string $idCapitulo, string $idCombate, string $estado, ?string $resultado, bool $nuevoIntento): void;
}
?>
