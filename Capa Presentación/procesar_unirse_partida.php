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

if ($auditoria->limiteSuperado('unirse_partida', 30, 10, $idUsuario)) {
    mostrarPaginaError(429);
}

$idPartida = filter_var($_POST['id_partida'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

if ($idPartida === false || mb_strlen($password) > 64) {
    mostrarPaginaError(400);
}

$auditoria->registrar('unirse_partida', 'info', $idUsuario, null, 'Partida ' . $idPartida);

header('Location: inicioHome.php');
exit();
