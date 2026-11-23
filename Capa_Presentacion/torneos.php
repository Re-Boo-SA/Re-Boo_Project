<?php
/* Torneos relámpago (Fase 4): lista, armar uno, inscribirse y el cuadro. La pantalla la arma js/torneos.js. */
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');
SesionSegura::exigirRol('jugador');
$idTorneo = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Torneos</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/paginas.css">
    <link rel="stylesheet" href="css/social.css">
</head>
<body class="cuerpo-pagina" data-idioma="<?= e($idioma) ?>"
      data-api="<?= e(urlAplicacion('api-torneos')) ?>"
      data-pagina="<?= e(urlAplicacion('torneos')) ?>"
      data-partida="<?= e(urlAplicacion('partida')) ?>"
      data-csrf="<?= e(SesionSegura::tokenCsrf()) ?>"
      data-torneo="<?= (int) $idTorneo ?>">
    <header class="barra-pagina">
        <a class="marca" href="<?= e(urlAplicacion('inicio')) ?>">DRAFT DER MAUER</a>
        <a class="volver" href="<?= e($idTorneo ? urlAplicacion('torneos') : urlAplicacion('inicio')) ?>"><?= $idTorneo ? 'Todos los torneos' : 'Volver al inicio' ?></a>
    </header>
    <main class="contenido">
        <section class="tarjeta-pagina">
            <h1 id="titulo-torneos">Torneos relámpago</h1>
            <p class="nota" id="nota-torneos">Eliminación directa de 4 u 8 jugadores, transmitidos desde la Torre de TV. El cuadro se arma por MMR; quien abandona o no se presenta, pierde. Al campeón: el logro «Campeón de Berlín» y 200 XP.</p>
            <a class="aviso-torneo" id="aviso-mi-partida" hidden>🏆 Tu partida del torneo empezó · Jugar</a>
            <div id="torneos"></div>
            <p class="aviso-social" id="aviso-torneos" role="status" hidden></p>
        </section>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
    <script src="js/torneos.js"></script>
</body>
</html>
