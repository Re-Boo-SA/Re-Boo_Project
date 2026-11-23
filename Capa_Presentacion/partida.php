<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

$idPartida = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idPartida === false || $idPartida === null) {
    header('Location: ' . urlAplicacion('inicio'));
    exit();
}
$idioma = $_SESSION['idioma'] ?? 'es';
$mensaje = SesionSegura::tomarFlash('flash_perfil');
$idTorneo = (new FachadaLogica())->retornoITorneoLogica()->torneoDePartida($idPartida);   // null si no es de un torneo
$esDesafio = (new FachadaLogica())->retornoIMisionLogica()->esDesafio($idPartida);       // partida de una misión de tablero
$capituloDesafio = $esDesafio ? (new FachadaLogica())->retornoIMisionLogica()->capituloDeDesafio($idPartida) : null;   // o de la campaña
$volverDesafio = $capituloDesafio !== null ? urlAplicacion('campana') . '?capitulo=' . rawurlencode($capituloDesafio) : urlAplicacion('mundo');
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Partida</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/partida.css">
</head>
<body class="cuerpo-partida"
      data-idioma="<?= e($idioma) ?>"
      data-id="<?= (int) $idPartida ?>"
      data-csrf="<?= e(SesionSegura::tokenCsrf()) ?>"
      data-api="<?= e(urlAplicacion('api-partida')) ?>"
      data-inicio="<?= e(urlAplicacion('inicio')) ?>"
      data-login="<?= e(urlAplicacion('login')) ?>"
      data-mundo="<?= e(urlAplicacion('mundo')) ?>"
      data-desafio="<?= $esDesafio ? '1' : '0' ?>"
      data-volver="<?= e($volverDesafio) ?>"
      data-espectar="<?= ($_GET['espectar'] ?? '') === '1' ? '1' : '0' ?>">
    <p class="aviso-espectador" id="aviso-espectador" hidden>Estás mirando esta partida. No ves las manos de nadie.</p>

    <header class="barra-partida">
        <a class="marca" href="<?= e(urlAplicacion('inicio')) ?>" aria-label="Volver al inicio">DRAFT DER MAUER</a>
        <div class="barra-centro">
            <h1 id="nombre-partida">Cargando partida…</h1>
            <p id="progreso-partida" class="progreso"></p>
        </div>
        <div class="barra-derecha">
            <div class="reloj" id="reloj" hidden>
                <span id="reloj-numero">60</span>
                <svg viewBox="0 0 36 36" aria-hidden="true"><circle cx="18" cy="18" r="15.5" id="reloj-arco" /></svg>
            </div>
            <a class="boton-reportar" href="<?= e(urlAplicacion('reportes')) ?>?partida=<?= (int) $idPartida ?>" target="_blank" rel="noopener" title="Reportar un problema o a un jugador (se abre en otra pestaña)" aria-label="Reportar">⚑</a>
            <button type="button" class="boton-salir" id="btn-salir">Salir</button>
        </div>
    </header>

    <?php if ($mensaje !== ''): ?>
        <div class="aviso aviso-fijo" role="status"><?= e($mensaje) ?></div>
    <?php endif; ?>
    <div class="aviso" id="aviso" role="status" aria-live="polite" hidden></div>

    <!-- Sala de espera -->
    <main class="sala-espera" id="vista-espera" hidden>
        <section class="tarjeta-espera">
            <h2>Esperando jugadores</h2>
            <p id="espera-detalle" class="espera-detalle"></p>
            <ol class="asientos" id="asientos"></ol>
            <div class="acciones-espera" id="acciones-espera">
                <button type="button" class="boton boton-primario" id="btn-agregar-bot">Agregar un bot</button>
                <button type="button" class="boton" id="btn-completar-bots">Completar con bots</button>
            </div>
            <p class="nota">La partida empieza sola cuando se llenan los lugares. Con bots se juega igual, pero no se ganan puntos de tienda.</p>
        </section>
    </main>

    <!-- Juego -->
    <main class="mesa" id="vista-juego" hidden>
        <section class="zona-tablero">
            <div class="estado-turno" id="estado-turno"></div>

            <div class="tablero" id="mi-tablero" aria-label="Tu tablero">
                <div class="lado lado-oeste">
                    <article class="recinto" data-recinto="oeste"><header><h3>Oeste</h3><span class="cupo"></span></header><div class="casillas"></div></article>
                    <article class="recinto" data-recinto="fabrica"><header><h3>Fábrica</h3><span class="cupo"></span></header><div class="casillas"></div></article>
                    <article class="recinto" data-recinto="espias"><header><h3>Espías</h3><span class="cupo"></span></header><div class="casillas"></div></article>
                </div>
                <article class="recinto recinto-muro" data-recinto="muro"><header><h3>Muro</h3><span class="cupo"></span></header><div class="casillas"></div></article>
                <div class="lado lado-este">
                    <article class="recinto" data-recinto="politicos"><header><h3>Políticos</h3><span class="cupo"></span></header><div class="casillas"></div></article>
                    <article class="recinto" data-recinto="este"><header><h3>Este</h3><span class="cupo"></span></header><div class="casillas"></div></article>
                    <article class="recinto" data-recinto="militares"><header><h3>Militares</h3><span class="cupo"></span></header><div class="casillas"></div></article>
                </div>
            </div>

            <section class="mano" aria-labelledby="titulo-mano">
                <div class="mano-encabezado">
                    <h2 id="titulo-mano">Tu mano</h2>
                    <p id="pista-mano" class="pista"></p>
                </div>
                <div class="fichas-mano" id="fichas-mano"></div>
                <div class="acciones-turno">
                    <button type="button" class="boton boton-primario" id="btn-confirmar" disabled>Confirmar jugada</button>
                </div>
            </section>
        </section>

        <aside class="panel-lateral">
            <section class="caja caja-dado" id="caja-dado">
                <img id="cara-dado" src="img/juego/CaraVaciaDadoDraftDerMauer.png" alt="Dado sin tirar">
                <div>
                    <h2 id="nombre-cara">Dado</h2>
                    <p id="detalle-cara"></p>
                    <button type="button" class="boton boton-primario" id="btn-tirar" hidden>Tirar el dado</button>
                </div>
            </section>

            <section class="caja">
                <h2>Puntos</h2>
                <p class="total-propio"><span id="mi-total">0</span> <small>puntos</small></p>
                <dl class="desglose" id="desglose"></dl>
            </section>

            <section class="caja">
                <h2>Jugadores</h2>
                <ul class="lista-jugadores" id="lista-jugadores"></ul>
            </section>

            <section class="caja caja-historial">
                <h2>Historial</h2>
                <ol class="historial" id="historial"></ol>
            </section>
        </aside>

        <section class="rivales" id="rivales" aria-label="Tableros de los demás jugadores"></section>
    </main>

    <!-- Resultado final -->
    <div class="capa-final" id="capa-final" hidden>
        <section class="tarjeta-final" role="dialog" aria-modal="true" aria-labelledby="titulo-final">
            <h2 id="titulo-final">Fin de la partida</h2>
            <p id="subtitulo-final"></p>
            <table class="tabla-final">
                <thead><tr><th>Puesto</th><th>Jugador</th><th>Puntos</th><th>Oeste</th><th>Fábrica</th><th>Espías</th><th>Políticos</th><th>Este</th><th>Militares</th><th>Muro</th><th>Recibe</th></tr></thead>
                <tbody id="cuerpo-final"></tbody>
            </table>
            <p id="nota-final" class="nota"></p>
            <div class="acciones-final">
                <?php if ($esDesafio): ?>
                    <a class="boton boton-primario" href="<?= e($volverDesafio) ?>"><?= $capituloDesafio !== null ? 'Volver a la historia' : 'Volver a Berlín' ?></a>
                <?php endif; ?>
                <?php if ($idTorneo !== null): ?>
                    <a class="boton boton-primario" href="<?= e(urlAplicacion('torneos')) ?>?id=<?= (int) $idTorneo ?>">Volver al torneo</a>
                <?php endif; ?>
                <a class="boton<?= $idTorneo === null && !$esDesafio ? ' boton-primario' : '' ?>" href="<?= e(urlAplicacion('inicio')) ?>">Volver al inicio</a>
                <button type="button" class="boton" id="btn-ver-tableros">Ver los tableros</button>
            </div>
        </section>
    </div>

    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
    <script src="js/partida.js"></script>
</body>
</html>
