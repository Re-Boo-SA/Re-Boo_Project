<?php

if (defined('REBOO_ARRANQUE')) {
    return;
}
define('REBOO_ARRANQUE', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/CabecerasSeguridad.php');
require_once(__DIR__ . '/SesionSegura.php');

$modoDesarrollo = SeguridadLogica::modoDesarrollo();
error_reporting(E_ALL);
ini_set('display_errors', $modoDesarrollo ? '1' : '0');
ini_set('log_errors', '1');
$carpetaLogs = AuditoriaLogica::carpetaLogs();
if (is_dir($carpetaLogs) || @mkdir($carpetaLogs, 0750, true)) {
    ini_set('error_log', $carpetaLogs . '/php_errores.log');
}

function mostrarPaginaError(int $codigo): never
{
    $codigoError = $codigo;
    require(__DIR__ . '/../error.php');
    exit();
}

set_exception_handler(function (Throwable $e) use ($modoDesarrollo) {
    error_log('Excepción no controlada: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if ($modoDesarrollo) {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
        exit();
    }
    mostrarPaginaError(500);
});

CabecerasSeguridad::enviar(defined('PAGINA_USA_RECAPTCHA'));

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 3 * 1024 * 1024) {
    mostrarPaginaError(413);
}

SesionSegura::iniciar();

$paginasPublicas = ['index.php', 'login.php', 'registro.php', 'verificar_2fa.php', 'error.php', 'estado.php'];
$paginaActual = basename($_SERVER['SCRIPT_NAME'] ?? '');

if (!in_array($paginaActual, $paginasPublicas, true) && !SesionSegura::estaAutenticado()) {
    SesionSegura::redirigirAlLogin('Acceso restringido: iniciá sesión para continuar.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !SesionSegura::verificarCsrf()) {
    (new AuditoriaLogica())->registrar('csrf_invalido', 'aviso', isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null, null, $paginaActual);
    mostrarPaginaError(403);
}

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
