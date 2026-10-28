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

$nombre = ($jugador && method_exists($jugador, 'getNombreUsuario') && $jugador->getNombreUsuario()) ? $jugador->getNombreUsuario() : ($_SESSION['nombre_usuario'] ?? 'Jugador');
$biografia = ($jugador && method_exists($jugador, 'getBiografia') && $jugador->getBiografia()) ? $jugador->getBiografia() : ($_SESSION['biografia'] ?? '');
$estadoActual = ($jugador && method_exists($jugador, 'getEstado') && $jugador->getEstado()) ? $jugador->getEstado() : ($_SESSION['estado'] ?? 'ocupado');

$partidasJugadas = $jugador ? $jugador->getPartidasJugadas() : ($_SESSION['partidas_jugadas'] ?? 0);
$partidasGanadas = $jugador ? $jugador->getPartidasGanadas() : ($_SESSION['partidas_ganadas'] ?? 0);

$rutaFoto = 'img/perfiles/perfil_' . (int) $idUsuario . '.png';
$existeFoto = file_exists(__DIR__ . '/' . $rutaFoto);
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

    <div id="overlay-sidebar" class="overlay-sidebar"></div>

    <aside id="sidebar-menu" class="sidebar-menu">
        <div class="sidebar-header">
            <h2>Barrera configuración</h2>
            <button type="button" class="btn-cerrar-sidebar" id="btn-cerrar-sidebar">×</button>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="sidebar-item" onclick="alert('Configuración')">Configuración</a>
            <a href="#" class="sidebar-item" onclick="alert('Amistades')">Amistades</a>
            <a href="#" class="sidebar-item" onclick="alert('Añadir')">Añadir</a>
            <div class="sidebar-item modo-selector">
                <span>Modo</span>
                <div class="toggle-modo" id="btn-toggle-modo">
                    <span class="icono-modo luna activo" id="btn-luna">🌙</span>
                    <span class="icono-modo sol" id="btn-sol">☀️</span>
                </div>
            </div>
            <a href="CalculadoraT.php" class="sidebar-item">Calculadora</a>
        </nav>
    </aside>

    <header class="encabezado-home">
        <button type="button" class="boton-menu" id="btn-abrir-sidebar" title="Configuración">
            <span></span><span></span><span></span>
        </button>

        <div class="titulo-principal">
            <h1>DRAFT DER MAUER</h1>
        </div>

        <div class="icono-perfil" id="btn-abrir-perfil" title="Editar Perfil">
            <?php if ($existeFoto): ?>
                <img src="<?= $rutaFoto ?>?v=<?= time() ?>" alt="Perfil" class="imagen-avatar-circulo" />
            <?php else: ?>
                <svg class="svg-usuario-defecto" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            <?php endif; ?>
        </div>
    </header>

    <main class="panel-central">
        <div class="columna-botones">
            <button type="button" class="btn-mauer btn-principal" id="btn-abrir-crear-partida">CREAR PARTIDA</button>
            <button type="button" class="btn-mauer btn-icono" onclick="alert('Tienda')">T</button>
        </div>

        <div class="seccion-tablero">
            <img src="img/TABLERO.png" alt="Mapa" class="imagen-tablero" />
        </div>

        <div class="columna-botones">
            <a href="unirse_partida.php" class="btn-mauer btn-principal" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">UNIRSE A PARTIDA</a>
            <button type="button" class="btn-mauer btn-icono" onclick="alert('Info')">I</button>
        </div>
    </main>

    <footer class="pie-home">
        <button type="button" class="btn-mauer btn-principal btn-historial" onclick="alert('Jugadas: <?= $partidasJugadas ?>\nGanadas: <?= $partidasGanadas ?>')">HISTORIAL</button>
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

            <form action="procesar_crear_partida.php" method="POST" id="form-crear-partida">
                <div class="bloque-campo-partida">
                    <label class="label-rojo">NOMBRE DE LA PARTIDA</label>
                    <input type="text" name="nombre_partida" id="input-nombre-partida" class="input-partida" placeholder="Ej: La Batalla del Norte" required />
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
                    <input type="password" name="password" id="input-pass-partida" class="input-partida" placeholder="Contraseña de la sala (Obligatoria si es privada)" />
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

            <form action="./perfil.php" method="POST" enctype="multipart/form-data">
                <div class="seccion-perfil">
                    <div class="titulo-seccion-modal">Foto de perfil</div>
                    <div class="contenido-foto-perfil">
                        <div class="preview-avatar">
                            <?php if ($existeFoto): ?>
                                <img src="<?= $rutaFoto ?>?v=<?= time() ?>" alt="Preview" id="img-preview" class="imagen-avatar-circulo" />
                            <?php else: ?>
                                <svg class="svg-usuario-defecto" id="svg-preview" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            <?php endif; ?>
                            <span class="indicador-estado <?= htmlspecialchars($estadoActual) ?>" id="indicador-preview"></span>
                        </div>
                        <div class="entradas-foto">
                            <input type="file" name="foto_perfil" id="subir-imagen" class="input-archivo-oculto" accept="image/*" onchange="previewFoto(this)">
                            <label for="subir-imagen" class="btn-modal-input" id="lbl-subir">Subir imagen...</label>
                        </div>
                    </div>
                </div>

                <div class="seccion-perfil">
                    <div class="titulo-seccion-modal">Información</div>
                    <div class="campo-bloque">
                        <label>Usuario</label>
                        <input type="text" name="nombre_usuario" value="<?= htmlspecialchars($nombre) ?>" class="input-modal-texto" required />
                    </div>
                    <div class="campo-bloque">
                        <label>Biografía</label>
                        <textarea name="biografia" placeholder="Escribe algo sobre ti..." class="input-modal-texto area-texto"><?= htmlspecialchars($biografia) ?></textarea>
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
                <button type="button" class="btn-logout-modal" id="btn-cerrar-sesion">Cerrar sesión</button>
            </div>
        </div>
    </div>

    <script src="js/home.js"></script>
    <script src="js/traduccion.js"></script>
    <script>
        if ('<?= $_SESSION['idioma'] ?? "es" ?>' === 'en') {
            document.addEventListener('DOMContentLoaded', aplicarTraduccionIngles);
        }
    </script>
</body>
</html>