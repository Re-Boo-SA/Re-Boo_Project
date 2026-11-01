<?php
session_start();

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'jugador') {
    $_SESSION['flash_error'] = 'Acceso restringido: Inicia sesión con tu cuenta de Jugador.';
    header('Location: login.php');
    exit();
}

$idUsuario = $_SESSION['usuario_id'] ?? 0;
$fachada = new FachadaLogica();
$jugador = $fachada->retornoIJugadorLogica()->obtenerJugador($idUsuario);

$nombre = $jugador ? $jugador->getNombreUsuario() : ($_SESSION['nombre_usuario'] ?? 'Jugador');
$partidasJugadas = $jugador ? $jugador->getPartidasJugadas() : ($_SESSION['partidas_jugadas'] ?? 0);
$partidasGanadas = $jugador ? $jugador->getPartidasGanadas() : ($_SESSION['partidas_ganadas'] ?? 0);

$winrate = ($partidasJugadas > 0) ? round(($partidasGanadas / $partidasJugadas) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Inicio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css" />
</head>

<body class="cuerpo-home-pc">

    <header class="encabezado-home">
        <button type="button" class="boton-menu" title="Configuración" onclick="alert('Abriendo menú de configuración...')">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="titulo-principal">
            <h1>DRAFT DER MAUER</h1>
        </div>

        <div class="icono-perfil" title="<?= htmlspecialchars($nombre) ?>"></div>
    </header>

    <!-- Tablero y botones laterales -->
    <main class="panel-central">
        
        <div class="columna-botones">
            <button type="button" class="btn-mauer btn-principal" id="btn-crear-partida"
                onclick="alert('Iniciando creación de partida...')">CREAR PARTIDA</button>
            <button type="button" class="btn-mauer btn-icono" id="btn-tienda"
                onclick="alert('Tienda')">T</button>
        </div>

        <div class="seccion-tablero">
            <img src="img/TABLERO.png" alt="Mapa de Draft Der Mauer" class="imagen-tablero" />
        </div>

        <div class="columna-botones">
            <button type="button" class="btn-mauer btn-principal" id="btn-buscar-partida"
                onclick="alert('Buscando partida...')">BUSCAR PARTIDA</button>
            <button type="button" class="btn-mauer btn-icono" id="btn-info"
                onclick="alert('Información')">I</button>
        </div>

    </main>

    <!-- Pie con Historial -->
    <footer class="pie-home">
        <button type="button" class="btn-mauer btn-principal btn-historial" id="btn-historial-partidas"
            onclick="alert('Historial:\nPartidas jugadas: <?= $partidasJugadas ?>\nGanadas: <?= $partidasGanadas ?>\nWinrate: <?= $winrate ?>%')">HISTORIAL</button>
    </footer>

</body>

</html>