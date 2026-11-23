<?php
/*
 * API JSON de la partida en línea.
 *   GET  api-partida?id=5&v=12          -> vista (con "sinCambios" si la versión no cambió)
 *   GET  api-partida?id=5&espectar=1    -> vista de espectador (sin manos)
 *   POST api-partida  id=5&accion=...   -> tirar | colocar | quitar | mover | confirmar | salir | agregar_bot | completar_bots
 * El token CSRF va en la cabecera X-CSRF-Token (lo verifica arranque.php).
 */
define('RESPUESTA_JSON', true);
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');
header('Content-Type: application/json; charset=UTF-8');

$idUsuario = (int) $_SESSION['usuario_id'];
$logica = (new FachadaLogica())->retornoIPartidaLogica();

function responder(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$idPartida = filter_var($metodo === 'POST' ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idPartida === false || $idPartida === null) {
    responder(['exito' => false, 'mensaje' => 'Falta el número de partida.'], 400);
}

if ($metodo === 'GET') {
    $version = filter_var($_GET['v'] ?? -1, FILTER_VALIDATE_INT, ['options' => ['default' => -1]]);
    // ?espectar=1: mirar sin jugar (Fase 4). Nunca se ven las manos.
    $vista = ($_GET['espectar'] ?? '') === '1' ? $logica->vistaEspectador($idPartida, $idUsuario, $version) : $logica->vista($idPartida, $idUsuario, $version);
    responder($vista, $vista['exito'] ? 200 : 404);
}

if ($metodo !== 'POST') {
    mostrarPaginaError(405);
}

$acciones = ['tirar', 'colocar', 'quitar', 'mover', 'confirmar', 'salir', 'agregar_bot', 'completar_bots'];
$accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
if (!in_array($accion, $acciones, true)) {
    responder(['exito' => false, 'mensaje' => 'Acción desconocida.'], 400);
}

// Límite generoso (un turno son 2 o 3 acciones): solo frena scripts que spamean.
if (!SesionSegura::dentroDelLimite('accion_partida', 120, 60)) {
    mostrarPaginaError(429);
}

$datos = [
    'nro' => filter_var($_POST['nro'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]),
    'recinto' => is_string($_POST['recinto'] ?? null) ? preg_replace('/[^a-z_]/', '', $_POST['recinto']) : '',
];
$resultado = $logica->accion($idPartida, $idUsuario, $accion, $datos);
responder($resultado, $resultado['exito'] ? 200 : 422);
