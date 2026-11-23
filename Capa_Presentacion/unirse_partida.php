<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

$idUsuario = (int) $_SESSION['usuario_id'];
$logicaPartidas = (new FachadaLogica())->retornoIPartidaLogica();
$partidaActual = $logicaPartidas->partidaActiva($idUsuario);
$mensaje = SesionSegura::tomarFlash('flash_unirse');
$partidas = array_map(fn($p) => [
    'id' => (int) $p['IDPartida'],
    'nombre' => $p['Nombre'],
    'tipo' => $p['Tipo'],
    'modo' => $p['Modo'],
    'creador' => $p['Creador'] ?? '',
    'jugadores' => (int) $p['Jugadores'] . '/' . (int) $p['MaxJugadores'],
], $logicaPartidas->listarSalas());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Unirse</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
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
            <?php if ($mensaje !== ''): ?>
                <div class="alerta alerta-error"><span><?= e($mensaje) ?></span></div>
            <?php endif; ?>
            <?php if ($partidaActual !== null): ?>
                <a href="<?= e(urlAplicacion('partida')) ?>?id=<?= (int) $partidaActual ?>" class="btn-mauer boton-enlace-bloque">VOLVER A TU PARTIDA</a>
            <?php endif; ?>
            <div class="lista-partidas">
                <?php if ($partidas === []): ?>
                    <p class="lista-vacia">No hay salas esperando jugadores. ¡Creá una desde el inicio!</p>
                <?php endif; ?>
                <?php foreach ($partidas as $p): ?>
                    <div class="fila-partida" tabindex="0" role="button" data-id="<?= (int) $p['id'] ?>" data-tipo="<?= e($p['tipo']) ?>" data-nombre="<?= e($p['nombre']) ?>">
                        <div class="info-sala">
                            <span class="icono-candado"><?= $p['tipo'] === 'privada' ? '🔒' : '🔓' ?></span>
                            <span class="nombre-sala"><?= e($p['nombre']) ?></span>
                            <?php if ($p['modo'] === 'personalizada'): ?><span class="etiqueta-modo">Personalizada</span><?php endif; ?>
                            <small class="creador-sala"><?= e($p['creador']) ?></small>
                        </div>
                        <span class="badge-jugadores"><?= e($p['jugadores']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="<?= e(urlAplicacion('unirse')) ?>" class="boton-enviar boton-enlace-bloque boton-secundario">Actualizar lista</a>
            <a href="<?= e(urlAplicacion('inicio')) ?>" class="boton-enviar boton-enlace-bloque">Volver</a>
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
            fila.addEventListener('keydown', ev => { if (ev.key === 'Enter') seleccionarPartida(fila.dataset.id, fila.dataset.tipo, fila.dataset.nombre); });
        });
        document.getElementById('modal-password').addEventListener('click', ev => {
            if (ev.target.id === 'modal-password') ev.target.classList.remove('mostrar');
        });
    </script>
</body>
</html>