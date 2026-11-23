<?php
/*
 * Puntuación de la calculadora (modo seguimiento) con el MISMO motor que la
 * partida en línea. Recibe las fichas colocadas y devuelve los puntos.
 *   POST api-calcular  colocaciones=[{"ficha":"cabo","recinto":"fabrica","posicion":1}, ...]
 */
define('RESPUESTA_JSON', true);
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/MotorJuego.php');

SesionSegura::exigirRol('jugador');
header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    mostrarPaginaError(405);
}
if (!SesionSegura::dentroDelLimite('calcular', 240, 60)) {
    mostrarPaginaError(429);
}

$colocaciones = json_decode(is_string($_POST['colocaciones'] ?? null) ? $_POST['colocaciones'] : '[]', true);
if (!is_array($colocaciones) || count($colocaciones) > 60) {
    mostrarPaginaError(400);
}

$tablero = [];
foreach (array_values($colocaciones) as $i => $c) {
    $tipo = is_array($c) ? ($c['ficha'] ?? '') : '';
    $recinto = is_array($c) ? ($c['recinto'] ?? '') : '';
    if (!isset(MotorJuego::CATEGORIA_DE[$tipo]) || !isset(MotorJuego::RECINTOS[$recinto])) {
        mostrarPaginaError(400);
    }
    $tablero[] = ['nro' => $i + 1, 'tipo' => $tipo, 'recinto' => $recinto, 'posicion' => max(1, (int) ($c['posicion'] ?? 1)), 'ronda' => 1, 'turno' => 1];
}

$puntos = (new MotorJuego())->puntuar($tablero);
// Como la calculadora muestra el punto del político dentro del recinto donde está, se suma por recinto.
$porRecinto = array_fill_keys(array_keys(MotorJuego::RECINTOS), 0);
foreach ($tablero as $f) {
    $porRecinto[$f['recinto']] += $puntos['detalle'][$f['nro']] ?? 0;
}
echo json_encode([
    'exito' => true,
    'total' => $puntos['total'],
    'porRecinto' => $porRecinto,
    'detalle' => array_values(array_map(fn($f) => $puntos['detalle'][$f['nro']] ?? 0, $tablero)),
], JSON_UNESCAPED_UNICODE);
