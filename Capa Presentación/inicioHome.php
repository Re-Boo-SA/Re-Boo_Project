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
$correo = $jugador ? $jugador->getCorreo() : ($_SESSION['correo'] ?? '');
$fichasActuales = $jugador ? $jugador->getFichasActuales() : ($_SESSION['fichas_actuales'] ?? 0);
$cantidadFichas = $jugador ? $jugador->getCantidadFichas() : ($_SESSION['cantidad_fichas'] ?? 0);
$pntsPartida = $jugador ? $jugador->getPntsPartida() : ($_SESSION['pnts_partida'] ?? 0);
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

<body>
    <div class="contenedor-app fondo-inicio">
        <div class="pantalla pantalla-inicio">

            <header class="tarjeta-usuario">
                <div class="info-usuario">
                    <span class="nombre-usuario"><?= htmlspecialchars($nombre) ?></span>
                    <span class="skins-usuario">Skins: 0</span>
                    <span class="puntos-usuario"><?= htmlspecialchars((string) $pntsPartida) ?> Puntos</span>
                </div>
            </header>

            <main class="menu-principal">
                <button type="button" class="boton-principal" id="btn-crear-partida"
                    onclick="alert('Iniciando creación de partida en el servidor Re-Boo...')">Crear partida</button>
                <button type="button" class="boton-principal" id="btn-buscar-partida"
                    onclick="alert('Buscando partida en el servidor Re-Boo...')">Buscar partida</button>
                <button type="button" class="boton-secundario" id="btn-historial-partidas"
                    onclick="alert('Historial de Jugador:\nPartidas jugadas: <?= $partidasJugadas ?>\nPartidas ganadas: <?= $partidasGanadas ?>\nPorcentaje de victorias: <?= $winrate ?>%')">Historial</button>
            </main>

            <footer class="navegacion-inferior">
                <a href="logout.php" class="boton-enlace" id="btn-cerrar-sesion"
                    style="color: #ffffff; text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; height: 100%; font-family: 'Saira Stencil One', cursive, sans-serif; letter-spacing: 0.5px;">
                    Cerrar sesión
                </a>
            </footer>

        </div>
    </div>
</body>

</html>