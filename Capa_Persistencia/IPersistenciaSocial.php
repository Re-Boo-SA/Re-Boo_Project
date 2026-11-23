<?php
interface IPersistenciaSocial
{
    public function iniciarTransaccion(): void;
    public function confirmar(): void;
    public function revertir(): void;
    public function jugador(int $idUsuario): ?array;
    public function jugadorPorNombre(string $nombre): ?array;
    public function buscar(string $prefijo, int $excluir): array;
    public function logros(int $idUsuario): array;
    public function perfilPersonal(int $idUsuario): ?array;
    public function guardarPerfilPersonal(int $idUsuario, array $campos): void;
    public function amistad(int $a, int $b): ?array;
    public function pedirAmistad(int $de, int $para): void;
    public function aceptarAmistad(int $solicitante, int $destino): bool;
    public function borrarAmistad(int $a, int $b): void;
    public function amigos(int $idUsuario): array;
    public function solicitudes(int $idUsuario): array;
    public function bloqueado(int $a, int $b): bool;
    public function bloquear(int $idUsuario, int $idOtro): void;
    public function desbloquear(int $idUsuario, int $idOtro): void;
    public function bloqueados(int $idUsuario): array;
    public function idsBloqueadosEntreAmbos(int $idUsuario): array;
    public function presencias(array $ids): array;
    public function grupoDe(int $idUsuario): ?array;
    public function bloquearGrupo(int $idGrupo): ?array;
    public function miembros(int $idGrupo): array;
    public function crearGrupo(int $idLider): int;
    public function agregarMiembro(int $idGrupo, int $idUsuario): void;
    public function quitarMiembro(int $idUsuario): void;
    public function cambiarLider(int $idGrupo, int $idUsuario): void;
    public function borrarGrupo(int $idGrupo): void;
    public function mensajesGrupo(int $idGrupo, int $desde): array;
    public function decirGrupo(int $idGrupo, int $idUsuario, string $texto): void;
    public function invitar(string $tipo, int $de, int $para, ?int $idGrupo, ?int $idPartida): void;
    public function invitacionesDe(int $idUsuario): array;
    public function invitacion(int $idInvitacion): ?array;
    public function borrarInvitacion(int $idInvitacion): void;
    public function borrarInvitacionesEntre(int $a, int $b): void;
    public function partidaVisible(int $idPartida): ?array;
    public function partidasEnCursoDeDistrito(int $idDistrito): array;
}
?>
