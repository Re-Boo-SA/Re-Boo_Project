<?php
/*
 * API de la campaña (etapa D).
 *   GET  api-campana?id=prologo                 -> guion, mapas, progreso y decisiones
 *   POST api-campana accion=empezar id          -> empezar o retomar
 *   POST api-campana accion=guardar id punto    -> punto de guardado (en orden)
 *   POST api-campana accion=decidir id clave valor
 *   POST api-campana accion=completar id        -> fin del capítulo (premio una sola vez)
 *   POST api-campana accion=combate_iniciar id combate          -> combate nuevo (o reintento)
 *   POST api-campana accion=combate_accion id combate unidad tipo(mover|disparar|cubrirse) x y blanco
 *   POST api-campana accion=combate_turno id combate            -> terminar el turno: juegan los rivales
 */
define('RESPUESTA_JSON', true);
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRolRevalidando('jugador', 30);
header('Content-Type: application/json; charset=UTF-8');
$idUsuario = (int) $_SESSION['usuario_id'];
$campana = (new FachadaLogica())->retornoICampanaLogica();
$texto = fn(string $k) => is_string($_POST[$k] ?? $_GET[$k] ?? null) ? mb_substr((string) ($_POST[$k] ?? $_GET[$k]), 0, 60) : '';

function responder(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($metodo === 'GET') {
    session_write_close();
    $r = $campana->capitulo($idUsuario, $texto('id'));
    responder($r, $r['exito'] ? 200 : 404);
}
if ($metodo !== 'POST') {
    mostrarPaginaError(405);
}
if (!SesionSegura::dentroDelLimite('campana', 150, 60)) {   // un combate son varias acciones seguidas
    responder(['exito' => false, 'mensaje' => 'Demasiadas acciones seguidas.'], 429);
}
session_write_close();
$r = match ($texto('accion')) {
    'empezar' => $campana->empezar($idUsuario, $texto('id')),
    'guardar' => $campana->guardar($idUsuario, $texto('id'), $texto('punto')),
    'decidir' => $campana->decidir($idUsuario, $texto('id'), $texto('clave'), $texto('valor')),
    'completar' => $campana->completar($idUsuario, $texto('id')),
    'combate_iniciar' => $campana->combateIniciar($idUsuario, $texto('id'), $texto('combate')),
    'combate_accion' => $campana->combateAccion($idUsuario, $texto('id'), $texto('combate'), $texto('unidad'), $texto('tipo'),
        (int) filter_var($_POST['x'] ?? 0, FILTER_VALIDATE_INT), (int) filter_var($_POST['y'] ?? 0, FILTER_VALIDATE_INT), $texto('blanco')),
    'combate_turno' => $campana->combateTurno($idUsuario, $texto('id'), $texto('combate')),
    'partida_iniciar' => $campana->partidaIniciar($idUsuario, $texto('id'), $texto('paso')),
    'partida_estado' => $campana->partidaEstado($idUsuario, $texto('id'), $texto('paso')),
    'interrogatorio' => $campana->interrogatorio($idUsuario, $texto('id'), $texto('paso'),
        array_map('intval', array_slice(explode(',', is_string($_POST['respuestas'] ?? null) ? $_POST['respuestas'] : ''), 0, 20))),
    'persecucion_iniciar' => $campana->persecucionIniciar($idUsuario, $texto('id'), $texto('paso')),
    'agujas' => $campana->agujas($idUsuario, $texto('id'), $texto('paso'), (function () {
        $r = [];
        foreach (explode(',', is_string($_POST['estados'] ?? null) ? $_POST['estados'] : '') as $par) {
            [$k, $v] = array_pad(explode(':', $par, 2), 2, '');
            if (preg_match('/^[A-Z]$/', $k)) $r[$k] = $v;
        }
        return $r;
    })()),
    'persecucion_elegir' => $campana->persecucionElegir($idUsuario, $texto('id'), $texto('paso'), (int) filter_var($_POST['opcion'] ?? -1, FILTER_VALIDATE_INT, ['options' => ['default' => -1]])),
    default => ['exito' => false, 'mensaje' => 'Acción desconocida.'],
};
responder($r, $r['exito'] ? 200 : 422);
