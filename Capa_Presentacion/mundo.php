<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

$fachada = new FachadaLogica();
$partidaActiva = $fachada->retornoIPartidaLogica()->partidaActiva((int) $_SESSION['usuario_id']);
$enlaces = [
    'ranking' => urlAplicacion('ranking'), 'tienda' => urlAplicacion('tienda'),
    'galeria' => urlAplicacion('galeria'), 'competitivo' => urlAplicacion('competitivo'),
    'partida' => urlAplicacion('partida'), 'inicio' => urlAplicacion('inicio'), 'torneos' => urlAplicacion('torneos'), 'campana' => urlAplicacion('campana'), 'personalizar' => urlAplicacion('personalizar'),
];
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Berlín</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/mundo.css">
    <link rel="stylesheet" href="css/social.css">
</head>
<body class="cuerpo-mundo"
      data-idioma="<?= e($idioma) ?>"
      data-csrf="<?= e(SesionSegura::tokenCsrf()) ?>"
      data-api="<?= e(urlAplicacion('api-mundo')) ?>"
      data-api-social="<?= e(urlAplicacion('api-social')) ?>"
      data-enlaces="<?= e(json_encode($enlaces)) ?>"
      data-partida-activa="<?= $partidaActiva === null ? '' : (int) $partidaActiva ?>">

    <div id="juego" role="img" aria-label="Mapa de Berlín. Usá las flechas o WASD para caminar y E para entrar a los edificios."></div>

    <header class="hud-arriba">
        <div class="hud-grupo">
            <a class="hud-boton" href="<?= e(urlAplicacion('inicio')) ?>">Menú</a>
            <span class="hud-boton reloj-mundo" id="reloj-mundo" aria-live="off">--:--</span>
            <button type="button" class="hud-boton progreso" id="btn-misiones"><span id="nivel-texto">Nv 1</span><span class="barra-xp" aria-hidden="true"><span id="barra-xp"></span></span><span class="texto-misiones">Misiones</span></button>
        </div>
        <div class="marcador" aria-live="polite">
            <span class="bando-oeste" id="marcador-oeste">0</span>
            <div class="barra-dominio" aria-hidden="true"><span id="barra-oeste"></span><span id="barra-este"></span></div>
            <span class="bando-este" id="marcador-este">0</span>
            <small id="temporada">Temporada 1</small>
        </div>
        <div class="hud-grupo">
            <button type="button" class="hud-boton" id="btn-vista" aria-pressed="false" aria-label="Primera persona" title="Primera persona">👁</button>
            <button type="button" class="hud-boton" id="btn-diario" aria-label="Diario del día">📰</button>
            <button type="button" class="hud-boton" id="btn-gestos" aria-label="Gestos">🙋</button>
            <button type="button" class="hud-boton con-insignia" id="btn-social">Social<span class="insignia" id="insignia-social" hidden></span></button>
            <button type="button" class="hud-boton" id="btn-sonido" aria-label="Sonido">🔊</button>
            <button type="button" class="hud-boton" id="btn-apariencia">Apariencia</button>
            <span class="hud-boton mi-bando" id="mi-bando">Sin bando</span>
        </div>
    </header>

    <?php if ($partidaActiva !== null): ?>
        <a class="aviso-partida" href="<?= e(urlAplicacion('partida')) ?>?id=<?= (int) $partidaActiva ?>">Tenés una partida en curso: volver</a>
    <?php endif; ?>

    <div class="indicacion" id="indicacion" hidden></div>
    <canvas id="minimapa" width="192" height="112" aria-hidden="true"></canvas>

    <form class="chat" id="form-chat" autocomplete="off">
        <label class="sr" for="texto-chat">Mensaje</label>
        <input id="texto-chat" maxlength="80" placeholder="Enter para escribir a los que están cerca…">
        <button type="submit">Decir</button>
    </form>
    <p class="ayuda" id="ayuda">WASD o flechas para caminar · clic para ir a un lugar · E para entrar</p>

    <!-- Panel de un edificio -->
    <aside class="panel" id="panel" hidden aria-labelledby="panel-titulo">
        <button type="button" class="cerrar" id="panel-cerrar" aria-label="Cerrar">×</button>
        <h2 id="panel-titulo"></h2>
        <p id="panel-texto"></p>
        <div id="panel-cafe" hidden>
            <h3>Mesas esperando</h3>
            <ul class="mesas" id="lista-mesas"></ul>
            <h3>Armar una mesa acá</h3>
            <div class="crear-mesa">
                <label>Jugadores
                    <select id="jugadores-mesa"><option>2</option><option>3</option><option selected>4</option><option>5</option></select>
                </label>
                <button type="button" class="boton-principal" id="btn-crear-mesa">Armar mesa</button>
            </div>
            <p class="nota" id="nota-influencia"></p>
        </div>
        <a class="boton-principal" id="panel-enlace" hidden>Entrar</a>
    </aside>

    <a class="aviso-torneo flotante" id="aviso-torneo" hidden>🏆 Tu partida del torneo empezó · Jugar</a>

    <p class="radio" id="radio" aria-live="off"></p>

    <!-- Diario del día -->
    <aside class="panel panel-diario" id="panel-diario" hidden aria-labelledby="diario-nombre">
        <button type="button" class="cerrar" id="cerrar-diario" aria-label="Cerrar">×</button>
        <header class="cabecera-diario">
            <h2 id="diario-nombre"></h2>
            <p id="diario-fecha"></p>
        </header>
        <div class="titulares" id="diario-titulares"></div>
        <button type="button" class="boton-secundario" id="diario-otro"></button>
    </aside>

    <!-- Gestos (también con las teclas 1 a 4) -->
    <div class="menu-gestos" id="menu-gestos" hidden>
        <button type="button" class="opcion-gesto" data-gesto="1"><kbd>1</kbd> Saludar</button>
        <button type="button" class="opcion-gesto" data-gesto="2"><kbd>2</kbd> Aplaudir</button>
        <button type="button" class="opcion-gesto" data-gesto="3"><kbd>3</kbd> Encogerse de hombros</button>
        <button type="button" class="opcion-gesto" data-gesto="4"><kbd>4</kbd> Sentarse</button>
    </div>

    <!-- Social: amigos, solicitudes, invitaciones y grupo -->
    <aside class="panel panel-social" id="panel-social" hidden aria-labelledby="titulo-social">
        <button type="button" class="cerrar" id="cerrar-social" aria-label="Cerrar">×</button>
        <h2 id="titulo-social">Social</h2>
        <div id="social-mundo" class="panel-social-contenido"></div>
    </aside>

    <!-- Diálogo con un contacto -->
    <aside class="panel panel-dialogo" id="panel-dialogo" hidden aria-labelledby="dialogo-nombre">
        <div class="dialogo-cabecera">
            <img class="retrato" id="dialogo-retrato" src="" alt="" width="96" height="96">
            <div>
                <h2 id="dialogo-nombre"></h2>
                <p class="dialogo-rol" id="dialogo-rol"></p>
                <p class="dialogo-estado" id="dialogo-estado"></p>
            </div>
        </div>
        <p class="dialogo-texto" id="dialogo-texto"></p>
        <div class="dialogo-acciones" id="dialogo-acciones"></div>
    </aside>

    <!-- Misiones y logros -->
    <aside class="panel panel-misiones" id="panel-misiones" hidden aria-labelledby="titulo-misiones">
        <button type="button" class="cerrar" id="cerrar-misiones" aria-label="Cerrar">×</button>
        <h2 id="titulo-misiones">Misiones</h2>
        <p class="nota" id="resumen-misiones"></p>
        <ul class="lista-misiones" id="lista-misiones"></ul>
        <h3>Historia: Nacht über Berlin</h3>
        <ul class="lista-logros lista-historia" id="lista-historia"></ul>
        <h3>Dónde están ahora</h3>
        <ul class="lista-logros lista-contactos" id="lista-contactos"></ul>
        <h3>Logros</h3>
        <ul class="lista-logros" id="lista-logros"></ul>
    </aside>

    <!-- Apariencia del personaje -->
    <aside class="panel panel-apariencia" id="panel-apariencia" hidden aria-labelledby="titulo-apariencia">
        <button type="button" class="cerrar" id="cerrar-apariencia" aria-label="Cerrar">×</button>
        <h2 id="titulo-apariencia">Apariencia</h2>
        <div class="apariencia">
            <canvas id="vista-apariencia" width="96" height="144" aria-hidden="true"></canvas>
            <div class="opciones-apariencia">
                <span>Piel</span>
                <div><button type="button" class="opcion-apariencia muestra piel-0" data-campo="piel" data-valor="0" aria-label="Piel clara"></button><button type="button" class="opcion-apariencia muestra piel-1" data-campo="piel" data-valor="1" aria-label="Piel media"></button><button type="button" class="opcion-apariencia muestra piel-2" data-campo="piel" data-valor="2" aria-label="Piel oscura"></button></div>
                <span>Pelo</span>
                <div><button type="button" class="opcion-apariencia muestra pelo-0" data-campo="pelo" data-valor="0" aria-label="Pelo negro"></button><button type="button" class="opcion-apariencia muestra pelo-1" data-campo="pelo" data-valor="1" aria-label="Pelo castaño"></button><button type="button" class="opcion-apariencia muestra pelo-2" data-campo="pelo" data-valor="2" aria-label="Pelo rubio"></button><button type="button" class="opcion-apariencia muestra pelo-3" data-campo="pelo" data-valor="3" aria-label="Pelo canoso"></button></div>
                <span>Gorro</span>
                <div><button type="button" class="opcion-apariencia" data-campo="gorro" data-valor="0">Sin gorro</button><button type="button" class="opcion-apariencia" data-campo="gorro" data-valor="1">Gorra</button><button type="button" class="opcion-apariencia" data-campo="gorro" data-valor="2">Sombrero</button></div>
            </div>
        </div>
        <button type="button" class="boton-principal" id="guardar-apariencia">Guardar</button>
    </aside>

    <!-- Elegir bando -->
    <div class="capa" id="capa-bando" hidden>
        <section class="tarjeta-bando" role="dialog" aria-modal="true" aria-labelledby="titulo-bando">
            <h2 id="titulo-bando">¿De qué lado del Muro estás?</h2>
            <p>Cada partida que juegues suma influencia para tu bando en el distrito donde la jugaste. El bando que domine más distritos al final de la temporada (30 días) gana. Podés cambiar de bando una vez por temporada.</p>
            <div class="opciones-bando">
                <button type="button" class="opcion-bando oeste" data-bando="oeste">
                    <img src="img/USA.png" alt=""><strong>Oeste</strong><span>Estados Unidos y aliados</span>
                </button>
                <button type="button" class="opcion-bando este" data-bando="este">
                    <img src="img/URSS.png" alt=""><strong>Este</strong><span>Unión Soviética</span>
                </button>
            </div>
        </section>
    </div>

    <div class="aviso" id="aviso" role="status" aria-live="polite" hidden></div>

    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
    <script src="js/vendor/phaser.min.js"></script>
    <script src="js/mundo_vivo.js"></script>
    <script src="js/mundo_primera.js"></script>
    <script src="js/mundo_sonido.js"></script>
    <script src="js/apariencia.js"></script>
    <script src="js/social.js"></script>
    <script src="js/mundo.js"></script>
</body>
</html>
