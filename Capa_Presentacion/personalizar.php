<?php
/* Personalizar: tu personaje del mundo abierto y tu perfil (al estilo Discord). La pantalla la arma js/personalizar.js. */
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
    <title>Draft Der Mauer - Personalizar</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/paginas.css">
    <link rel="stylesheet" href="css/social.css">
    <link rel="stylesheet" href="css/personalizar.css">
</head>
<body class="cuerpo-pagina" data-idioma="<?= e($idioma) ?>"
      data-api-mundo="<?= e(urlAplicacion('api-mundo')) ?>"
      data-api-social="<?= e(urlAplicacion('api-social')) ?>"
      data-csrf="<?= e(SesionSegura::tokenCsrf()) ?>">
    <header class="barra-pagina">
        <a class="marca" href="<?= e(urlAplicacion('inicio')) ?>">DRAFT DER MAUER</a>
        <a class="volver" href="<?= e(urlAplicacion('mundo')) ?>">Volver a Berlín</a>
    </header>
    <main class="contenido">
        <section class="tarjeta-pagina">
            <h1>Personalizar</h1>
            <div class="pestanas-personalizar" role="tablist">
                <button type="button" role="tab" id="tab-personaje" aria-selected="true">Tu personaje</button>
                <button type="button" role="tab" id="tab-perfil" aria-selected="false">Tu perfil</button>
            </div>

            <!-- Tu personaje -->
            <div class="editor" id="editor-personaje">
                <div class="vista-previa">
                    <canvas id="vista-personaje" width="192" height="288" aria-label="Vista previa del personaje"></canvas>
                    <div class="botones-vista">
                        <button type="button" id="girar">⟳ Girar</button>
                        <button type="button" id="saludar">👋 Saludar</button>
                        <button type="button" id="azar">🎲 Al azar</button>
                    </div>
                </div>
                <div class="controles" id="controles-personaje"></div>
            </div>

            <!-- Tu perfil -->
            <div class="editor" id="editor-perfil" hidden>
                <div class="vista-previa vista-tarjeta">
                    <p class="nota">Así te ven los demás:</p>
                    <section class="tarjeta-perfil" id="tarjeta-previa"></section>
                </div>
                <div class="controles" id="controles-perfil"></div>
            </div>

            <div class="acciones-personalizar">
                <button type="button" class="boton-principal" id="guardar">Guardar</button>
                <p class="aviso-social" id="aviso" role="status" hidden></p>
            </div>
        </section>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
    <script src="js/apariencia.js"></script>
    <script src="js/personalizar.js"></script>
</body>
</html>
