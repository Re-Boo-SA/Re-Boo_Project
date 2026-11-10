<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mostrarPaginaError(405);
}

$idUsuario = (int) $_SESSION['usuario_id'];
$fachada = new FachadaLogica();
$auditoria = $fachada->retornoIAuditoriaLogica();

if ($auditoria->limiteSuperado('partida_creada', 10, 10, $idUsuario)) {
    mostrarPaginaError(429);
}

$nombrePartida = SeguridadLogica::normalizarTexto(is_string($_POST['nombre_partida'] ?? null) ? $_POST['nombre_partida'] : '');
$tipoPartida = $_POST['tipo_partida'] ?? 'publica';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$numJugadores = filter_var($_POST['num_jugadores'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2, 'max_range' => 5]]);

$valido = $nombrePartida !== ''
    && mb_strlen($nombrePartida) <= 40
    && in_array($tipoPartida, ['publica', 'privada'], true)
    && $numJugadores !== false
    && ($tipoPartida === 'publica' || ($password !== '' && mb_strlen($password) <= 64));

if (!$valido) {
    SesionSegura::flash('flash_perfil', 'Los datos de la partida no son válidos.');
    header('Location: inicio');
    exit();
}

$auditoria->registrar('partida_creada', 'info', $idUsuario, null, $tipoPartida . ', ' . $numJugadores . ' jugadores');

header('Location: inicio');
exit();
