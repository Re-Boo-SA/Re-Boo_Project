<?php
interface IPersistenciaMisiones
{
    public function misionesDe(int $idUsuario): array;
    public function iniciar(int $idUsuario, string $idMision): bool;
    public function avanzar(int $idUsuario, string $idMision, int $paso): void;
    public function completar(int $idUsuario, string $idMision): bool;
    public function partidasDesde(int $idUsuario, string $desde, bool $ganadas, ?string $lado): int;
    public function sumarRecompensa(int $idUsuario, int $xp, int $puntos): void;
    public function xpDe(int $idUsuario): int;
    public function logrosDe(int $idUsuario): array;
    public function otorgarLogro(int $idUsuario, string $idLogro): bool;
    public function registrarIntento(int $idPartida, int $idUsuario, string $idMision, int $paso): void;
    public function intentos(int $idUsuario, string $idMision, int $paso): array;
    public function marcarIntento(int $idPartida, string $resultado): void;
    public function resultadoDesafio(int $idPartida, int $idUsuario): ?array;
    public function tableroFinal(int $idPartida, int $idUsuario): array;
    public function esDesafio(int $idPartida): bool;
    public function misionDePartida(int $idPartida): ?string;
    public function iniciarTransaccion(): void;
    public function confirmar(): void;
    public function revertir(): void;
}
?>
