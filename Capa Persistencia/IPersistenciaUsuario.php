<?php

require_once(__DIR__ . '/../DTO/UsuarioDTO.php');

interface IPersistenciaUsuario
{
    public function buscarPorIdentificador(string $identificador): ?Usuario;
    public function buscarPorId(int $idUsuario): ?Usuario;
    public function existeCorreo(string $correo): bool;
    public function existeNombreUsuario(string $nombreUsuario, int $excluirId = 0): bool;
    public function actualizarContra(int $idUsuario, string $contraHash): bool;
    public function obtenerDosFactores(int $idUsuario): array;
    public function guardarDosFactores(int $idUsuario, ?string $secretoCifrado, ?string $codigosRecuperacion): bool;
}
?>
