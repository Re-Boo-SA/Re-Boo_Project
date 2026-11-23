<?php
interface ISocialLogica
{
    public function perfil(int $visitante, int $idUsuario, string $nombre = ''): array;
    public function buscar(int $visitante, string $texto): array;
    public function solicitar(int $idUsuario, int $idOtro): array;
    public function responder(int $idUsuario, int $idSolicitante, bool $aceptar): array;
    public function eliminar(int $idUsuario, int $idOtro): array;
    public function bloquear(int $idUsuario, int $idOtro): array;
    public function desbloquear(int $idUsuario, int $idOtro): array;
    public function panel(int $idUsuario): array;
    public function resumen(int $idUsuario): array;
    public function invitarAMesa(int $idUsuario, int $idOtro): array;
    public function invitarAGrupo(int $idUsuario, int $idOtro): array;
    public function aceptarInvitacion(int $idUsuario, int $idInvitacion): array;
    public function rechazarInvitacion(int $idUsuario, int $idInvitacion): array;
    public function salirDelGrupo(int $idUsuario): array;
    public function expulsar(int $idLider, int $idOtro): array;
    public function grupo(int $idUsuario, int $desde = 0): array;
    public function decirAlGrupo(int $idUsuario, string $texto): array;
    public function puedeMirar(int $idUsuario, int $idPartida): array;
    public function partidasParaMirar(int $idDistrito): array;
    public function avisarAlGrupo(int $idLider, int $idPartida): void;
    public function bloqueadosEntreAmbos(int $idUsuario): array;
}
?>
