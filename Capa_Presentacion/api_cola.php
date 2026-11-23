<?php
/*
 * API de la cola competitiva.
 *   GET  api-cola                 -> estado (y empareja si hay gente)
 *   POST api-cola accion=entrar   -> entra a la cola
 *   POST api-cola accion=salir    -> sale de la cola
 */
define('RESPUESTA_JSON', true);
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');
header('Content-Type: application/json; charset=UTF-8');

$idUsuario = (int) $_SESSION['usuario_id'];
$cola = (new FachadaLogica())->retornoIColaLogica();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!SesionSegura::dentroDelLimite('cola', 30, 60)) {
        mostrarPaginaError(429);
    }
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'entrar') {
        echo json_encode($cola->entrar($idUsuario), JSON_UNESCAPED_UNICODE);
    } elseif ($accion === 'salir') {
        $cola->salir($idUsuario);
        echo json_encode(['exito' => true]);
    } else {
        mostrarPaginaError(400);
    }
    exit();
}

echo json_encode(['exito' => true] + $cola->estado($idUsuario), JSON_UNESCAPED_UNICODE);
