<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['cambiar_idioma'])) {
    $_SESSION['idioma'] = ($_POST['idioma'] ?? '') === 'en' ? 'en' : 'es';
    header('Location: ' . urlAplicacion('inicio'));
    exit();
}

$idUsuario = (int) $_SESSION['usuario_id'];
$mensajePerfil = SesionSegura::tomarFlash('flash_perfil');
$fachada = new FachadaLogica();
$jugador = $fachada->retornoIJugadorLogica()->obtenerJugador($idUsuario);

$nombre = ($jugador && method_exists($jugador, 'getNombreUsuario') && $jugador->getNombreUsuario()) ? $jugador->getNombreUsuario() : ($_SESSION['nombre_usuario'] ?? 'Jugador');
$biografia = ($jugador && method_exists($jugador, 'getBiografia') && $jugador->getBiografia()) ? $jugador->getBiografia() : ($_SESSION['biografia'] ?? '');
$estadoActual = ($jugador && method_exists($jugador, 'getEstado') && $jugador->getEstado()) ? $jugador->getEstado() : ($_SESSION['estado'] ?? 'ocupado');

$partidasJugadas = $jugador ? $jugador->getPartidasJugadas() : ($_SESSION['partidas_jugadas'] ?? 0);
$partidasGanadas = $jugador ? $jugador->getPartidasGanadas() : ($_SESSION['partidas_ganadas'] ?? 0);

$rutaFoto = 'img/perfiles/perfil_' . (int) $idUsuario . '.png';
$existeFoto = file_exists(__DIR__ . '/' . $rutaFoto);
$versionFoto = $existeFoto ? (int) filemtime(__DIR__ . '/' . $rutaFoto) : 0;
?>
<!DOCTYPE html>
<html lang="<?= e($_SESSION['idioma'] ?? 'es') ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Inicio</title>
    <link rel="icon" type="image/png" href="./img/imgPestaña/LogoBabyDraftDerMauer.png" />
    <link rel="stylesheet" href="css/fuentes.css" />
    <link rel="stylesheet" href="css/estilo.css" />
</head>

<body class="cuerpo-home-pc" data-idioma="<?= e($_SESSION['idioma'] ?? 'es') ?>">

    <div id="overlay-sidebar" class="overlay-sidebar"></div>

    <aside id="sidebar-menu" class="sidebar-menu">
        <div class="sidebar-header">
            <h2>CONFIGURACIÓN</h2>
            <button type="button" class="btn-cerrar-sidebar" id="btn-cerrar-sidebar">×</button>
        </div>
        <div class="configuracion-contenido">
            <div class="configuracion-icono" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="currentColor">
                    <path d="m43 27.1-4-3.1a15.7 15.7 0 0 0 0-4l4-3.1-4-7-4.8 1.8a16.5 16.5 0 0 0-3.5-2L30 4h-8l-1 5.7a16.5 16.5 0 0 0-3.5 2L12.7 10l-4 7 4 3.1a15.7 15.7 0 0 0 0 4l-4 3.1 4 7 4.8-1.8a16.5 16.5 0 0 0 3.5 2L22 42h8l1-5.7a16.5 16.5 0 0 0 3.5-2l4.8 1.8 4-7ZM26 30a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" />
                </svg>
            </div>

            <section class="configuracion-grupo" aria-labelledby="config-audio">
                <h3 id="config-audio">AUDIO</h3>
                <label class="configuracion-control">
                    <span><strong>Volumen general</strong><output for="volumen-general">0%</output></span>
                    <input type="range" id="volumen-general" min="0" max="100" value="0" />
                </label>
                <label class="configuracion-control">
                    <span><strong>Efectos de sonido</strong><output for="volumen-sonido">0%</output></span>
                    <input type="range" id="volumen-sonido" min="0" max="100" value="0" />
                </label>
                <label class="configuracion-control">
                    <span><strong>Música de fondo</strong><output for="volumen-musica">0%</output></span>
                    <input type="range" id="volumen-musica" min="0" max="100" value="0" />
                </label>
            </section>

            <section class="configuracion-grupo" aria-labelledby="config-visual">
                <h3 id="config-visual">VISUAL</h3>
                <label class="configuracion-switch">
                    <span><strong>Modo oscuro</strong><small>Aplica tonos oscuros</small></span>
                    <input type="checkbox" id="config-modo-oscuro" />
                    <span class="switch-pista" aria-hidden="true"></span>
                </label>
                <div class="controles-preferencias controles-configuracion">
                    <button type="button" class="btn-modo-preferencia" aria-label="Cambiar tema" title="Cambiar tema">
                        <span class="icono-modo luna" aria-hidden="true">🌙</span>
                        <span class="icono-modo sol" aria-hidden="true">☀️</span>
                    </button>
                    <form method="post" class="form-idioma-preferencia">
                        <?= SesionSegura::campoCsrf() ?>
                        <label class="etiqueta-idioma-preferencia" for="idioma-configuracion">Idioma</label>
                        <select name="idioma" id="idioma-configuracion" aria-label="Idioma" data-autoenviar>
                            <option value="es" <?= ($_SESSION['idioma'] ?? 'es') === 'es' ? 'selected' : '' ?>>ESP</option>
                            <option value="en" <?= ($_SESSION['idioma'] ?? 'es') === 'en' ? 'selected' : '' ?>>ENG</option>
                        </select>
                        <input type="hidden" name="cambiar_idioma" value="1" />
                    </form>
                </div>
                <div class="configuracion-calidad">
                    <strong>Calidad de detalles</strong>
                    <div class="opciones-calidad" role="group" aria-label="Calidad de detalles">
                        <button type="button" data-calidad="baja">Baja</button>
                        <button type="button" data-calidad="media" class="activo">Media</button>
                        <button type="button" data-calidad="alta">Alta</button>
                    </div>
                </div>
            </section>

            <section class="configuracion-grupo" aria-labelledby="config-jugabilidad">
                <h3 id="config-jugabilidad">JUGABILIDAD</h3>
                <label class="configuracion-switch">
                    <span><strong>Animaciones</strong><small>Movimientos de piezas y transiciones</small></span>
                    <input type="checkbox" id="config-animaciones" checked />
                    <span class="switch-pista" aria-hidden="true"></span>
                </label>
            </section>

            <section class="configuracion-grupo configuracion-general" aria-labelledby="config-utilidades">
                <h3 id="config-utilidades">UTILIDADES</h3>
                <nav class="sidebar-nav" aria-label="Opciones generales">
                    <a href="<?= e(urlAplicacion('calculadora')) ?>" class="sidebar-item">Calculadora de puntos</a>
                </nav>
            </section>
        </div>
    </aside>

    <header class="encabezado-home">
        <button type="button" class="boton-menu" id="btn-abrir-sidebar" title="Configuración">
            <span></span><span></span><span></span>
        </button>

        <div class="titulo-principal">
            <h1>DRAFT DER MAUER</h1>
        </div>

        <div class="controles-preferencias controles-home">
            <button type="button" class="btn-modo-preferencia" aria-label="Cambiar tema" title="Cambiar tema">
                <span class="icono-modo luna" aria-hidden="true">🌙</span>
                <span class="icono-modo sol" aria-hidden="true">☀️</span>
            </button>
            <form method="post" class="form-idioma-preferencia">
                <?= SesionSegura::campoCsrf() ?>
                <label class="etiqueta-idioma-preferencia" for="idioma-home">Idioma</label>
                <select name="idioma" id="idioma-home" aria-label="Idioma" data-autoenviar>
                    <option value="es" <?= ($_SESSION['idioma'] ?? 'es') === 'es' ? 'selected' : '' ?>>ESP</option>
                    <option value="en" <?= ($_SESSION['idioma'] ?? 'es') === 'en' ? 'selected' : '' ?>>ENG</option>
                </select>
                <input type="hidden" name="cambiar_idioma" value="1" />
            </form>
        </div>

        <div class="icono-perfil" id="btn-abrir-perfil" title="Editar Perfil">
            <?php if ($existeFoto): ?>
                <img src="<?= e($rutaFoto) ?>?v=<?= $versionFoto ?>" alt="Perfil" class="imagen-avatar-circulo" />
            <?php else: ?>
                <svg class="svg-usuario-defecto" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($mensajePerfil !== ''): ?>
        <div class="alerta alerta-error alerta-home" id="alerta-perfil">
            <span><?= e($mensajePerfil) ?></span>
        </div>
    <?php endif; ?>

    <main class="panel-central">
        <div class="columna-botones">
            <button type="button" class="btn-mauer btn-principal" id="btn-abrir-crear-partida">CREAR PARTIDA</button>
            <a href="<?= e(urlAplicacion('tienda')) ?>" class="btn-mauer btn-icono btn-enlace-centrado" aria-label="Abrir tienda">
                <svg class="tienda-icono" viewBox="0 0 48 48" aria-hidden="true">
                    <path d="M9 8h30l4 12a5 5 0 0 1-9 3 5 5 0 0 1-10 0 5 5 0 0 1-10 0 5 5 0 0 1-9-3L9 8Zm0 17a8 8 0 0 0 5 2 8 8 0 0 0 5-2 8 8 0 0 0 5 2 8 8 0 0 0 5-2 8 8 0 0 0 5 2 8 8 0 0 0 5-2v18H9V25Z" />
                    <path d="M13 30h9v7h-9zm13 0h8v13h-8z" class="tienda-icono-detalle" />
                </svg>
            </a>
        </div>

        <div class="seccion-tablero">
            <img src="img/TABLERO.png" alt="Mapa" class="imagen-tablero" />
        </div>

        <div class="columna-botones">
            <a href="unirse" class="btn-mauer btn-principal btn-enlace-centrado">UNIRSE A PARTIDA</a>
            <button type="button" class="btn-mauer btn-icono btn-info" aria-label="Información">
                <svg viewBox="0 0 48 48" aria-hidden="true">
                    <circle cx="24" cy="24" r="22" />
                    <path d="M22 20h4v15h-4zM24 13a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" class="info-glifo" />
                </svg>
            </button>
        </div>
    </main>

    <footer class="pie-home">
        <section class="tarjeta-historial" aria-labelledby="titulo-historial">
            <h2 id="titulo-historial">HISTORIAL</h2>
            <div class="historial-resumen">
                <div class="historial-dato">
                    <span>Partidas jugadas</span>
                    <strong><?= (int) $partidasJugadas ?></strong>
                </div>
                <div class="historial-dato">
                    <span>Partidas ganadas</span>
                    <strong><?= (int) $partidasGanadas ?></strong>
                </div>
            </div>
        </section>
    </footer>

    <!-- Modal Crear Partida -->
    <div class="modal-perfil-overlay" id="modal-crear-partida">
        <div class="tarjeta-crear-partida">
            <div class="encabezado-crear-partida">
                <div class="icono-espadas">⚔️</div>
                <div class="titulos-crear-partida">
                    <h2>CREAR PARTIDA</h2>
                    <p>Configura tu sesión de juego</p>
                </div>
                <button type="button" class="btn-cerrar-modal" id="btn-cerrar-crear-partida">×</button>
            </div>

            <form action="crear-partida" method="POST" id="form-crear-partida">
                <?= SesionSegura::campoCsrf() ?>
                <div class="bloque-campo-partida">
                    <label class="label-rojo">NOMBRE DE LA PARTIDA</label>
                    <input type="text" name="nombre_partida" id="input-nombre-partida" class="input-partida" placeholder="Ej: La Batalla del Norte" required maxlength="40" />
                </div>

                <div class="bloque-campo-partida">
                    <label class="label-rojo">TIPO DE PARTIDA</label>
                    <div class="selector-tipo-partida">
                        <button type="button" class="btn-tipo-partida activo" id="btn-tipo-publica">🌐 PÚBLICA</button>
                        <button type="button" class="btn-tipo-partida" id="btn-tipo-privada">🔒 PRIVADA</button>
                    </div>
                    <input type="hidden" name="tipo_partida" id="input-tipo-partida" value="publica" />
                </div>

                <div class="bloque-campo-partida gap-pass">
                    <input type="password" name="password" id="input-pass-partida" class="input-partida" maxlength="64" placeholder="Contraseña de la sala (Obligatoria si es privada)" />
                    <span class="msj-error-pass" id="msj-error-pass">Para una partida privada la contraseña es obligatoria.</span>
                </div>

                <div class="bloque-campo-partida">
                    <label class="label-rojo">👥 NÚMERO DE JUGADORES</label>
                    <div class="grid-jugadores-selector">
                        <div class="tarjeta-num-jugadores" data-num="3"><span class="num-grande">3</span><span class="lbl-jugadores">JUGADORES</span></div>
                        <div class="tarjeta-num-jugadores activo" data-num="4"><span class="num-grande">4</span><span class="lbl-jugadores">JUGADORES</span></div>
                        <div class="tarjeta-num-jugadores" data-num="5"><span class="num-grande">5</span><span class="lbl-jugadores">JUGADORES</span></div>
                    </div>
                    <input type="hidden" name="num_jugadores" id="input-num-jugadores" value="4" />
                </div>

                <button type="submit" class="btn-mauer btn-enviar-crear-partida">CREAR PARTIDA</button>
            </form>
        </div>
    </div>

    <!-- Modal de Perfil -->
    <div class="modal-perfil-overlay" id="modal-perfil">
        <div class="tarjeta-perfil">
            <div class="encabezado-modal">
                <h2>Perfil</h2>
                <button type="button" class="btn-cerrar-modal" id="btn-cerrar-perfil">×</button>
            </div>

            <form action="perfil" method="POST" enctype="multipart/form-data">
                <?= SesionSegura::campoCsrf() ?>
                <div class="seccion-perfil">
                    <div class="titulo-seccion-modal">Foto de perfil</div>
                    <div class="contenido-foto-perfil">
                        <div class="preview-avatar">
                            <?php if ($existeFoto): ?>
                                <img src="<?= e($rutaFoto) ?>?v=<?= $versionFoto ?>" alt="Preview" id="img-preview" class="imagen-avatar-circulo" />
                            <?php else: ?>
                                <svg class="svg-usuario-defecto" id="svg-preview" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            <?php endif; ?>
                            <span class="indicador-estado <?= e($estadoActual) ?>" id="indicador-preview"></span>
                        </div>
                        <div class="entradas-foto">
                            <input type="file" name="foto_perfil" id="subir-imagen" class="input-archivo-oculto" accept="image/png, image/jpeg, image/gif, image/webp">
                            <label for="subir-imagen" class="btn-modal-input" id="lbl-subir">Subir imagen...</label>
                        </div>
                    </div>
                </div>

                <div class="seccion-perfil">
                    <div class="titulo-seccion-modal">Información</div>
                    <div class="campo-bloque">
                        <label>Usuario</label>
                        <input type="text" name="nombre_usuario" value="<?= e($nombre) ?>" class="input-modal-texto" required minlength="3" maxlength="30" />
                    </div>
                    <div class="campo-bloque">
                        <label>Biografía</label>
                        <textarea name="biografia" placeholder="Escribe algo sobre ti..." class="input-modal-texto area-texto" maxlength="300"><?= e($biografia) ?></textarea>
                    </div>
                </div>

                <div class="seccion-perfil">
                    <div class="titulo-seccion-modal">Estado</div>
                    <div class="grid-estados">
                        <label class="opcion-estado <?= $estadoActual === 'en_linea' ? 'activo' : '' ?>">
                            <input type="radio" name="estado" value="en_linea" <?= $estadoActual === 'en_linea' ? 'checked' : '' ?>>
                            <span class="punto-estado en-linea"></span> En línea
                        </label>
                        <label class="opcion-estado <?= $estadoActual === 'ocupado' ? 'activo' : '' ?>">
                            <input type="radio" name="estado" value="ocupado" <?= $estadoActual === 'ocupado' ? 'checked' : '' ?>>
                            <span class="punto-estado ocupado"></span> Ocupado
                        </label>
                        <label class="opcion-estado <?= $estadoActual === 'no_molestar' ? 'activo' : '' ?>">
                            <input type="radio" name="estado" value="no_molestar" <?= $estadoActual === 'no_molestar' ? 'checked' : '' ?>>
                            <span class="punto-estado no-molestar"></span> No molestar
                        </label>
                        <label class="opcion-estado <?= $estadoActual === 'invisible' ? 'activo' : '' ?>">
                            <input type="radio" name="estado" value="invisible" <?= $estadoActual === 'invisible' ? 'checked' : '' ?>>
                            <span class="punto-estado invisible"></span> Invisible
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-mauer btn-guardar-perfil">GUARDAR CAMBIOS</button>
            </form>

            <div class="contenedor-logout-modal">
                <!-- El logout va por POST con token CSRF -->
                <form action="cerrar-sesion" method="POST" id="form-cerrar-sesion">
                    <?= SesionSegura::campoCsrf() ?>
                    <button type="button" class="btn-logout-modal" id="btn-cerrar-sesion">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-perfil-overlay" id="modal-info" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="titulo-modal-info" hidden>
        <section class="tarjeta-perfil tarjeta-info">
            <div class="encabezado-modal">
                <h2 id="titulo-modal-info">Información del juego</h2>
                <button type="button" class="btn-cerrar-modal" id="btn-cerrar-info" aria-label="Cerrar">×</button>
            </div>
            <p>Draft Der Mauer es un juego de estrategia por turnos ambientado en el Muro de Berlín. Crea una partida o únete a una sala para jugar.</p>
            <p>La calculadora te ayuda a registrar las fichas y consultar el puntaje de cada recinto.</p>
            <a class="btn-mauer btn-principal btn-enlace-centrado tarjeta-info-enlace" href="<?= e(urlAplicacion('calculadora')) ?>">Abrir calculadora</a>
        </section>
    </div>

    <script src="js/comun.js"></script>
    <script src="js/preferencias.js"></script>
    <script src="js/home.js"></script>
    <script src="js/traduccion.js"></script>
</body>
</html>