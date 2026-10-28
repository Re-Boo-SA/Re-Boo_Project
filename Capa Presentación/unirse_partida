<?php
session_start();
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'jugador') {
    header('Location: login.php');
    exit();
}

// Simulación de listado (reemplazar luego con tu lógica de BDD)
$partidas = [
    ['id' => 1, 'nombre' => 'Batalla del Norte', 'tipo' => 'publica', 'jugadores' => '3/4'],
    ['id' => 2, 'nombre' => 'Muro de Berlin Pro', 'tipo' => 'privada', 'jugadores' => '2/5'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Unirse</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
</head>
<body class="cuerpo-home-pc">
    <div class="contenedor-app">
        <header class="encabezado">
            <h1 class="titulo">DRAFT DER MAUER</h1>
            <p class="subtitulo">UNIRSE A PARTIDA</p>
        </header>

        <div class="tarjeta-formulario" style="max-width: 600px; margin: auto;">
            <div class="lista-partidas">
                <?php foreach ($partidas as $p): ?>
                    <div class="fila-partida" onclick="seleccionarPartida(<?= $p['id'] ?>, '<?= $p['tipo'] ?>', '<?= htmlspecialchars($p['nombre']) ?>')">
                        <div class="info-sala">
                            <span class="icono-candado"><?= $p['tipo'] === 'privada' ? '🔒' : '🔓' ?></span>
                            <span class="nombre-sala"><?= htmlspecialchars($p['nombre']) ?></span>
                        </div>
                        <span class="badge-jugadores"><?= $p['jugadores'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="inicioHome.php" class="boton-enviar" style="display:block; text-align:center; text-decoration:none; margin-top:20px;">Volver</a>
        </div>
    </div>

    <!-- Modal Contraseña Sala Privada -->
    <div class="modal-perfil-overlay" id="modal-password">
        <div class="tarjeta-crear-partida">
            <div class="titulos-crear-partida" style="margin-bottom:15px;">
                <h2>PARTIDA PRIVADA</h2>
                <p id="nombre-sala-modal">Ingresa la contraseña</p>
            </div>
            <form action="procesar_unirse_partida.php" method="POST">
                <input type="hidden" name="id_partida" id="input-id-partida">
                <div class="bloque-campo-partida">
                    <input type="password" name="password" class="input-partida" placeholder="Contraseña" required>
                </div>
                <button type="submit" class="btn-mauer btn-enviar-crear-partida">UNIRSE</button>
            </form>
        </div>
    </div>

    <script>
        function seleccionarPartida(id, tipo, nombre) {
            if (tipo === 'privada') {
                document.getElementById('input-id-partida').value = id;
                document.getElementById('nombre-sala-modal').innerText = nombre;
                document.getElementById('modal-password').classList.add('mostrar');
            } else {
                window.location.href = 'procesar_unirse_partida.php?id_partida=' + id;
            }
        }
    </script>
</body>
</html>