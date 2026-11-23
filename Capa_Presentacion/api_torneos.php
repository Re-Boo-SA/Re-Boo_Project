<?php
/*
 * API de torneos relámpago (Fase 4).
 *   GET  api-torneos                      -> lista (avanza los que estén activos)
 *   GET  api-torneos?id=N                 -> detalle: jugadores, cuadro por rondas y mi partida pendiente
 *   POST api-torneos accion=crear nombre tamano(4|8) minutos(15|30|60)
 *   POST api-torneos accion=inscribir id | salir id
 */
define('RESPUESTA_JSON', true);
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRolRevalidando('jugador', 30);
header('Content-Type: application/json; charset=UTF-8');

$idUsuario = (int) $_SESSION['usuario_id'];
$torneos = (new FachadaLogica())->retornoITorneoLogica();

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

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($metodo === 'GET') {
    session_write_close();
    $id = entero('id');
    if ($id > 0) {
        $r = $torneos->detalle($idUsuario, $id);
        responder($r, $r['exito'] ? 200 : 404);
    }
    responder($torneos->listar($idUsuario));
}
if ($metodo !== 'POST') {
    mostrarPaginaError(405);
}
if (!SesionSegura::dentroDelLimite('torneos', 30, 60)) {
    responder(['exito' => false, 'mensaje' => 'Demasiadas acciones seguidas. Esperá un momento.'], 429);
}
session_write_close();
$accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
$r = match ($accion) {
    'crear' => $torneos->crear($idUsuario, is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : '', entero('tamano'), entero('minutos')),
    'inscribir' => $torneos->inscribir($idUsuario, entero('id')),
    'salir' => $torneos->salir($idUsuario, entero('id')),
    default => ['exito' => false, 'mensaje' => 'Acción desconocida.'],
};
responder($r, $r['exito'] ? 200 : 422);
