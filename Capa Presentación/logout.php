<?php
require_once(__DIR__ . '/Seguridad/arranque.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mostrarPaginaError(405);
}

$fachada = new FachadaLogica();
$fachada->retornoIAuditoriaLogica()->registrar('logout', 'info', (int) $_SESSION['usuario_id']);

SesionSegura::cerrar();

header('Location: login');
exit();
