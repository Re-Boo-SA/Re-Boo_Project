<?php
session_start();

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'jugador') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = $_SESSION['usuario_id'] ?? 0;
    $nuevoNombre = trim($_POST['nombre_usuario'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    $estado = $_POST['estado'] ?? 'ocupado';

    $fachada = new FachadaLogica();

    if ($idUsuario > 0 && !empty($nuevoNombre)) {
        $jugadorLogica = $fachada->retornoIJugadorLogica();
        $jugadorObj = $jugadorLogica->obtenerJugador($idUsuario);

        if ($jugadorObj) {
            // Actualizamos los datos dentro del objeto en memoria
            if (method_exists($jugadorObj, 'setNombreUsuario')) {
                $jugadorObj->setNombreUsuario($nuevoNombre);
            }
            if (method_exists($jugadorObj, 'setBiografia')) {
                $jugadorObj->setBiografia($biografia);
            }
            if (method_exists($jugadorObj, 'setEstado')) {
                $jugadorObj->setEstado($estado);
            }

            // Guardamos en la Base de Datos pasando las entidades
            $jugadorLogica->modificarJugador($jugadorObj, $jugadorObj);

            // Actualizamos la sesión activa de PHP
            $_SESSION['nombre_usuario'] = $nuevoNombre;
            $_SESSION['biografia'] = $biografia;
            $_SESSION['estado'] = $estado;
        }
    }

    // Procesar la subida de la foto de perfil
    if ($idUsuario > 0 && isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/img/perfiles/';
        
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $dir . 'perfil_' . (int) $idUsuario . '.png');
    }

    // Redirigir de vuelta al panel principal
    header('Location: inicioHome.php');
    exit();
}