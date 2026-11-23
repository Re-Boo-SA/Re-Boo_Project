<?php
/* Amigos, solicitudes, invitaciones, grupo y bloqueados (Fase 4). La pantalla la arma js/social.js. */
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');
SesionSegura::exigirRol('jugador');
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Amigos</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/paginas.css">
    <link rel="stylesheet" href="css/social.css">
</head>
<body class="cuerpo-pagina" data-idioma="<?= e($idioma) ?>"
      data-api-social="<?= e(urlAplicacion('api-social')) ?>"
      data-partida="<?= e(urlAplicacion('partida')) ?>"
      data-csrf="<?= e(SesionSegura::tokenCsrf()) ?>">
    <header class="barra-pagina">
        <a class="marca" href="<?= e(urlAplicacion('inicio')) ?>">DRAFT DER MAUER</a>
        <a class="volver" href="<?= e(urlAplicacion('inicio')) ?>">Volver al inicio</a>
    </header>
    <main class="contenido">
        <section class="tarjeta-pagina">
            <h1>Amigos</h1>
            <p class="nota">Agregá jugadores, mirá dónde están, invitalos a tu mesa o armá un grupo de hasta cuatro. En Berlín también podés tocar a cualquier jugador para ver su perfil.</p>
            <div id="social" class="panel-social-contenido"></div>
            <p class="aviso-social" id="aviso-social" role="status" hidden></p>
        </section>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
    <script src="js/apariencia.js"></script>
    <script src="js/social.js"></script>
    <script src="js/amigos.js"></script>
</body>
</html>
