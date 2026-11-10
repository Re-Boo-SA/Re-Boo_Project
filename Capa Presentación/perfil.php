<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mostrarPaginaError(405);
}

$idUsuario = (int) $_SESSION['usuario_id'];
$fachada = new FachadaLogica();
$jugadorLogica = $fachada->retornoIJugadorLogica();

$nuevoNombre = is_string($_POST['nombre_usuario'] ?? null) ? $_POST['nombre_usuario'] : '';
$biografia = is_string($_POST['biografia'] ?? null) ? $_POST['biografia'] : '';
$estado = is_string($_POST['estado'] ?? null) ? $_POST['estado'] : 'ocupado';

$resultado = $jugadorLogica->actualizarPerfil($idUsuario, $nuevoNombre, $biografia, $estado);

if ($resultado['exito']) {
    $jugador = $resultado['jugador'];
    $_SESSION['nombre_usuario'] = $jugador->getNombreUsuario();
    $_SESSION['biografia'] = $jugador->getBiografia();
    $_SESSION['estado'] = $jugador->getEstado();
} else {
    SesionSegura::flash('flash_perfil', $resultado['mensaje']);
}

if (isset($_FILES['foto_perfil']) && ($_FILES['foto_perfil']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $resultadoFoto = $jugadorLogica->guardarFotoPerfil($idUsuario, $_FILES['foto_perfil'], __DIR__ . '/img/perfiles');

    if (!$resultadoFoto['exito']) {
        SesionSegura::flash('flash_perfil', $resultadoFoto['mensaje']);
    }
}

header('Location: inicio');
exit();
