<?php
/*
 * API social (Fase 4).
 *   GET  api-social?accion=panel                 -> amigos (con dónde están), solicitudes, invitaciones, bloqueados, grupo
 *   GET  api-social?accion=perfil&id=N | nombre=X -> perfil público
 *   GET  api-social?accion=buscar&q=texto        -> jugadores por nombre
 *   GET  api-social?accion=grupo&desde=N         -> mi grupo y sus mensajes nuevos
 *   POST api-social accion=solicitar id | responder id aceptar(1/0) | eliminar id | bloquear id | desbloquear id
 *   POST api-social accion=invitar_mesa id | invitar_grupo id | aceptar_invitacion id | rechazar_invitacion id
 *   POST api-social accion=salir_grupo | expulsar id | decir_grupo texto
 */
define('RESPUESTA_JSON', true);
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRolRevalidando('jugador', 30);
header('Content-Type: application/json; charset=UTF-8');

$idUsuario = (int) $_SESSION['usuario_id'];
$social = (new FachadaLogica())->retornoISocialLogica();

function responder(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

function entero(string $clave, int $defecto = 0): int
{
    $valor = filter_var($_POST[$clave] ?? $_GET[$clave] ?? null, FILTER_VALIDATE_INT);
    return $valor === false || $valor === null ? $defecto : $valor;
}

function texto(string $clave): string
{
    $v = $_POST[$clave] ?? $_GET[$clave] ?? '';
    return is_string($v) ? $v : '';
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = texto('accion');

if ($metodo === 'GET') {
    session_write_close();
    switch ($accion) {
        case 'panel':
            responder($social->panel($idUsuario));
        case 'personalizacion':
            responder($social->personalizacion($idUsuario));
        case 'perfil':
            $r = $social->perfil($idUsuario, entero('id'), texto('nombre'));
            responder($r, $r['exito'] ? 200 : 404);
        case 'buscar':
            responder($social->buscar($idUsuario, texto('q')));
        case 'grupo':
            responder($social->grupo($idUsuario, entero('desde')));
    }
    responder(['exito' => false, 'mensaje' => 'Acción desconocida.'], 400);
}

if ($metodo !== 'POST') {
    mostrarPaginaError(405);
}
// Límite general de acciones sociales (pedir amistad, invitar, etc.) y uno aparte para el chat del grupo.
$limite = $accion === 'decir_grupo' ? ['social_chat', 20, 30] : ['social', 60, 60];
if (!SesionSegura::dentroDelLimite(...$limite)) {
    responder(['exito' => false, 'mensaje' => 'Demasiadas acciones seguidas. Esperá un momento.'], 429);
}
session_write_close();

$id = entero('id');
$r = match ($accion) {
    'guardar_personalizacion' => $social->guardarPersonalizacion($idUsuario, [
        'banner1' => texto('banner1'), 'banner2' => texto('banner2'), 'acento' => texto('acento'), 'estadoEmoji' => is_string($_POST['estadoEmoji'] ?? null) ? $_POST['estadoEmoji'] : '',
        'estadoTexto' => is_string($_POST['estadoTexto'] ?? null) ? $_POST['estadoTexto'] : '', 'pronombres' => is_string($_POST['pronombres'] ?? null) ? $_POST['pronombres'] : '',
        'insignias' => array_slice(array_filter(explode(',', texto('insignias'))), 0, 10), 'marco' => texto('marco')]),
    'solicitar' => $social->solicitar($idUsuario, $id),
    'responder' => $social->responder($idUsuario, $id, entero('aceptar') === 1),
    'eliminar' => $social->eliminar($idUsuario, $id),
    'bloquear' => $social->bloquear($idUsuario, $id),
    'desbloquear' => $social->desbloquear($idUsuario, $id),
    'invitar_mesa' => $social->invitarAMesa($idUsuario, $id),
    'invitar_grupo' => $social->invitarAGrupo($idUsuario, $id),
    'aceptar_invitacion' => $social->aceptarInvitacion($idUsuario, $id),
    'rechazar_invitacion' => $social->rechazarInvitacion($idUsuario, $id),
    'salir_grupo' => $social->salirDelGrupo($idUsuario),
    'expulsar' => $social->expulsar($idUsuario, $id),
    'decir_grupo' => $social->decirAlGrupo($idUsuario, texto('texto')),
    default => null,
};
if ($r === null) {
    responder(['exito' => false, 'mensaje' => 'Acción desconocida.'], 400);
}
responder($r, $r['exito'] ? 200 : 422);
