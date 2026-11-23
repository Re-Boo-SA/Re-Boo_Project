<?php
/* Campaña "Nacht über Berlin" (etapa D): escenas propias de cada jugador. La arma js/campana.js con el guion de datos/campana. */
require_once(__DIR__ . '/Seguridad/arranque.php');
SesionSegura::exigirRol('jugador');
$capitulo = is_string($_GET['capitulo'] ?? null) && preg_match('/^[a-z0-9_]{1,40}$/', $_GET['capitulo']) ? $_GET['capitulo'] : 'prologo';
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Nacht über Berlin</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/campana.css">
</head>
<body class="cuerpo-campana" data-idioma="<?= e($idioma) ?>" data-capitulo="<?= e($capitulo) ?>"
      data-api="<?= e(urlAplicacion('api-campana')) ?>" data-mundo="<?= e(urlAplicacion('mundo')) ?>" data-partida="<?= e(urlAplicacion('partida')) ?>" data-campana="<?= e(urlAplicacion('campana')) ?>"
      data-csrf="<?= e(SesionSegura::tokenCsrf()) ?>">
    <div id="juego" aria-label="Escena"></div>

    <div class="franja arriba" id="franja-arriba"></div>
    <div class="franja abajo" id="franja-abajo"></div>
    <div class="fundido" id="fundido"></div>
    <div class="tarjeta" id="tarjeta" hidden><p id="tarjeta-texto"></p></div>
    <p class="objetivo-campana" id="objetivo" hidden></p>
    <p class="mensaje-campana" id="mensaje" hidden></p>
    <button type="button" class="indicacion-campana" id="indicacion" hidden><kbd>E</kbd><span id="indicacion-texto"></span></button>

    <div class="dialogo-campana" id="dialogo" hidden role="dialog" aria-live="polite">
        <img class="retrato-campana" id="dialogo-retrato" src="" alt="" width="96" height="96">
        <div>
            <p class="nombre-campana" id="dialogo-nombre"></p>
            <p class="texto-campana" id="dialogo-texto"></p>
            <p class="seguir" id="dialogo-seguir">Espacio, Enter o tocá para seguir</p>
        </div>
    </div>
    <div class="eleccion" id="eleccion" hidden>
        <p id="eleccion-pregunta"></p>
        <div id="eleccion-opciones"></div>
        <div class="cuenta" id="eleccion-cuenta" hidden><span id="eleccion-cuenta-barra"></span></div>
        <p class="medidor" id="eleccion-medidor" hidden></p>
    </div>

    <div class="portada" id="portada">
        <div class="portada-caja">
            <h1>Nacht über Berlin</h1>
            <h2 id="portada-titulo"></h2>
            <p class="aviso-ficcion" id="portada-aviso"></p>
            <div class="portada-botones" id="portada-botones"></div>
            <p class="ayuda-campana">WASD o flechas para caminar · E para usar · Espacio para seguir los diálogos</p>
        </div>
    </div>
    <div class="portada" id="final" hidden>
        <div class="portada-caja">
            <h1 id="final-titulo"></h1>
            <p id="final-texto"></p>
            <div class="portada-botones"><a class="boton-campana" id="final-siguiente" href="#" hidden></a><a class="boton-campana secundario" id="final-volver" href="<?= e(urlAplicacion('mundo')) ?>">Volver a Berlín</a></div>
        </div>
    </div>
    <!-- Agujas: el plano de vías -->
    <aside class="panel-agujas" id="agujas" hidden>
        <p class="agujas-titulo" id="agujas-titulo"></p>
        <svg id="agujas-plano" viewBox="0 0 560 190" role="img" aria-label="Plano de vías"></svg>
        <p class="medidor" id="agujas-destino"></p>
        <p class="medidor" id="agujas-acoplada"></p>
        <p class="medidor" id="agujas-tiempo"></p>
    </aside>

    <!-- Cerradura: la ganzúa de Thomas -->
    <div class="cerradura" id="cerradura" hidden>
        <p id="cerradura-texto"></p>
        <div class="barra-cerradura"><span class="zona" id="cerradura-zona"></span><span class="marca" id="cerradura-marca"></span></div>
        <p class="medidor" id="cerradura-pernos"></p>
        <button type="button" class="boton-campana" id="cerradura-boton">Ahora</button>
    </div>

    <!-- Combate táctico por turnos -->
    <aside class="panel-combate" id="combate" hidden>
        <p class="combate-objetivo" id="combate-objetivo"></p>
        <p class="combate-ronda" id="combate-ronda"></p>
        <ul class="combate-unidades" id="combate-unidades"></ul>
        <p class="combate-ayuda" id="combate-ayuda">Elegí un personaje: clic en una casilla azul para moverte, o en un rival para dispararle. C: cubrirse · T: terminar turno · Tab: cambiar de personaje</p>
        <div class="combate-botones">
            <button type="button" class="boton-campana secundario" id="combate-cubrirse">Cubrirse</button>
            <button type="button" class="boton-campana" id="combate-terminar">Terminar turno</button>
            <button type="button" class="boton-campana" id="combate-reintentar" hidden>Reintentar</button>
        </div>
    </aside>
    <a class="salir-campana" href="<?= e(urlAplicacion('mundo')) ?>" id="salir">Salir</a>

    <script src="js/vendor/phaser.min.js"></script>
    <script src="js/campana.js"></script>
</body>
</html>
