<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

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
    <link rel="icon" type="image/png" href="./img/imgPestaña/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body class="cuerpo-home-pc" data-idioma="<?= e($_SESSION['idioma'] ?? 'es') ?>">
    <div class="contenedor-app">
        <header class="encabezado">
            <h1 class="titulo">DRAFT DER MAUER</h1>
            <p class="subtitulo">UNIRSE A PARTIDA</p>
        </header>

        <div class="tarjeta-formulario tarjeta-lista-partidas">
            <div class="lista-partidas">
                <?php foreach ($partidas as $p): ?>
                    <div class="fila-partida" data-id="<?= (int) $p['id'] ?>" data-tipo="<?= e($p['tipo']) ?>" data-nombre="<?= e($p['nombre']) ?>">
                        <div class="info-sala">
                            <span class="icono-candado"><?= $p['tipo'] === 'privada' ? '🔒' : '🔓' ?></span>
                            <span class="nombre-sala"><?= e($p['nombre']) ?></span>
                        </div>
                        <span class="badge-jugadores"><?= e($p['jugadores']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="inicio" class="boton-enviar boton-enlace-bloque">Volver</a>
        </div>
    </div>

    <!-- Modal Contraseña Sala Privada -->
    <div class="modal-perfil-overlay" id="modal-password">
        <div class="tarjeta-crear-partida">
            <div class="titulos-crear-partida titulos-con-margen">
                <h2>PARTIDA PRIVADA</h2>
                <p id="nombre-sala-modal">Ingresa la contraseña</p>
            </div>
            <form action="unirse-partida" method="POST">
                <?= SesionSegura::campoCsrf() ?>
                <input type="hidden" name="id_partida" id="input-id-partida">
                <div class="bloque-campo-partida">
                    <input type="password" name="password" class="input-partida" placeholder="Contraseña" required maxlength="64">
                </div>
                <button type="submit" class="btn-mauer btn-enviar-crear-partida">UNIRSE</button>
            </form>
        </div>
    </div>

    <!-- Unirse a una partida pública también va por POST con token CSRF -->
    <form action="unirse-partida" method="POST" id="form-unirse-publica">
        <?= SesionSegura::campoCsrf() ?>
        <input type="hidden" name="id_partida" id="input-id-partida-publica">
    </form>

    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
    <script nonce="<?= CabecerasSeguridad::nonce() ?>">
        function seleccionarPartida(id, tipo, nombre) {
            if (tipo === 'privada') {
                document.getElementById('input-id-partida').value = id;
                document.getElementById('nombre-sala-modal').innerText = nombre;
                document.getElementById('modal-password').classList.add('mostrar');
            } else {
                document.getElementById('input-id-partida-publica').value = id;
                document.getElementById('form-unirse-publica').submit();
            }
        }

        document.querySelectorAll('.fila-partida').forEach(fila => {
            fila.addEventListener('click', () => seleccionarPartida(fila.dataset.id, fila.dataset.tipo, fila.dataset.nombre));
        });
    </script>
</body>
</html>