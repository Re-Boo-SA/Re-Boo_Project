<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['cambiar_idioma'])) {
    $_SESSION['idioma'] = ($_POST['idioma'] ?? '') === 'en' ? 'en' : 'es';
    header('Location: ' . urlAplicacion('tienda'));
    exit();
}

$idUsuario = (int) $_SESSION['usuario_id'];
$fachada = new FachadaLogica();
$jugador = $fachada->retornoIJugadorLogica()->obtenerJugador($idUsuario);

if ($jugador === null) {
    mostrarPaginaError(500);
}

$skinsLogica = $fachada->retornoISkinsLogica();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (($_POST['accion'] ?? '') !== 'comprar') {
        mostrarPaginaError(400);
    }

    $idSkin = filter_var($_POST['id_skin'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $idFicha = filter_var($_POST['id_ficha'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($idSkin === false || $idFicha === false) {
        $resultadoCompra = ['exito' => false, 'mensaje' => 'La skin seleccionada no es válida.'];
    } else {
        try {
            $resultadoCompra = $skinsLogica->comprarSkin($idUsuario, $idSkin, $idFicha);
        } catch (RuntimeException $e) {
            error_log('Tienda: ' . $e->getMessage());
            $resultadoCompra = ['exito' => false, 'mensaje' => 'No se pudo completar la compra. Inténtalo nuevamente.'];
        }
    }

    SesionSegura::flash('flash_tienda', $resultadoCompra['mensaje']);
    SesionSegura::flash('flash_tienda_tipo', $resultadoCompra['exito'] ? 'exito' : 'error');
    header('Location: ' . urlAplicacion('tienda'));
    exit();
}

$mensajeTienda = SesionSegura::tomarFlash('flash_tienda');
$tipoMensaje = SesionSegura::tomarFlash('flash_tienda_tipo');
$saldo = $jugador->getPntsPartida();
$catalogo = [];
$inventario = [];
$errorCatalogo = '';
$errorInventario = '';

try {
    $catalogo = $skinsLogica->listarCatalogo();
} catch (RuntimeException $e) {
    error_log('Tienda: ' . $e->getMessage());
    $errorCatalogo = $e->getMessage();
}

if ($errorCatalogo === '') {
    try {
        $inventario = $skinsLogica->listarInventario($idUsuario);
    } catch (RuntimeException $e) {
        error_log('Tienda: ' . $e->getMessage());
        $errorInventario = $e->getMessage();
    }
}

$skinsCompradas = [];
foreach ($inventario as $skinComprada) {
    $skinsCompradas[$skinComprada['IDSkin'] . '-' . $skinComprada['IDFicha']] = true;
}
?>
<!DOCTYPE html>
<html lang="<?= e($_SESSION['idioma'] ?? 'es') ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tienda de skins | Draft Der Mauer</title>
    <link rel="icon" type="image/png" href="img/imgPestaña/LogoBabyDraftDerMauer.png" />
    <link rel="stylesheet" href="css/fuentes.css" />
    <link rel="stylesheet" href="css/estilo.css" />
</head>

<body class="tienda-body" data-idioma="<?= e($_SESSION['idioma'] ?? 'es') ?>">
    <header class="tienda-barra">
        <a href="<?= e(urlAplicacion('inicio')) ?>" class="tienda-marca">DRAFT DER MAUER</a>
        <nav class="tienda-enlaces" aria-label="Navegación de la tienda">
            <span class="tienda-saldo"><span>Mis puntos</span><strong>◉ <?= number_format($saldo, 0, ',', '.') ?></strong></span>
            <div class="controles-preferencias">
                <button type="button" class="btn-modo-preferencia" aria-label="Cambiar tema" title="Cambiar tema">
                    <span class="icono-modo luna" aria-hidden="true">🌙</span>
                    <span class="icono-modo sol" aria-hidden="true">☀️</span>
                </button>
                <form method="post" class="form-idioma-preferencia">
                    <?= SesionSegura::campoCsrf() ?>
                    <label class="etiqueta-idioma-preferencia" for="idioma-tienda">Idioma</label>
                    <select name="idioma" id="idioma-tienda" aria-label="Idioma" data-autoenviar>
                        <option value="es" <?= ($_SESSION['idioma'] ?? 'es') === 'es' ? 'selected' : '' ?>>ESP</option>
                        <option value="en" <?= ($_SESSION['idioma'] ?? 'es') === 'en' ? 'selected' : '' ?>>ENG</option>
                    </select>
                    <input type="hidden" name="cambiar_idioma" value="1" />
                </form>
            </div>
            <a href="<?= e(urlAplicacion('inicio')) ?>">Volver al inicio</a>
        </nav>
    </header>

    <main class="tienda-contenido">
        <section class="tienda-panel" aria-labelledby="tienda-titulo">
            <header class="tienda-cabecera-panel">
                <svg class="tienda-icono" viewBox="0 0 48 48" aria-hidden="true">
                    <path d="M8 16 11 7h26l3 9v4H8v-4Zm3 6h26v19h-9V29h-8v12h-9V22Z" />
                </svg>
                <h1 id="tienda-titulo">TIENDA</h1>
            </header>

            <?php if ($mensajeTienda !== ''): ?>
                <div class="tienda-aviso <?= $tipoMensaje === 'error' ? 'error' : 'exito' ?>" role="status">
                    <?= e($mensajeTienda) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorCatalogo !== ''): ?>
                <div class="tienda-aviso error" role="alert"><?= e($errorCatalogo) ?></div>
            <?php else: ?>
                <?php if ($errorInventario !== ''): ?>
                    <div class="tienda-aviso error" role="alert"><?= e($errorInventario) ?></div>
                <?php endif; ?>
                <div class="tienda-grid">
                    <?php foreach ($catalogo as $skin): ?>
                        <?php
                        $claveSkin = $skin['IDSkin'] . '-' . $skin['IDFicha'];
                        $comprada = isset($skinsCompradas[$claveSkin]);
                        $precio = (int) $skin['Precio'];
                        $urlImagen = trim((string) ($skin['URL'] ?? ''));
                        $tieneImagen = $urlImagen !== '' && $urlImagen !== 'skin.png';
                        ?>
                        <article class="skin-card">
                            <div class="skin-imagen">
                                <?php if ($tieneImagen): ?>
                                    <img src="<?= e($urlImagen) ?>" alt="<?= e($skin['NombreSkin']) ?>" loading="lazy" />
                                <?php else: ?>
                                    <svg class="skin-silueta" viewBox="0 0 64 80" aria-hidden="true">
                                        <path d="M32 5c-7 0-12 5-12 12 0 5 3 9 7 11v7c-3 3-6 7-8 12H15c-5 0-9 4-9 9v6h52v-6c0-5-4-9-9-9h-4c-2-5-5-9-8-12v-7c4-2 7-6 7-11 0-7-5-12-12-12Zm-6 57h12v5H26v-5Z" />
                                    </svg>
                                <?php endif; ?>
                                <span class="skin-especie"><?= e(ucfirst($skin['FichaEspecie'])) ?></span>
                            </div>
                            <div class="skin-info">
                                <h2><?= e($skin['NombreSkin']) ?></h2>
                                <div class="skin-pie">
                                    <strong class="skin-precio">◉ <?= number_format($precio, 0, ',', '.') ?></strong>
                                    <?php if ($comprada): ?>
                                        <span class="skin-comprada">En tu colección</span>
                                    <?php else: ?>
                                        <form action="<?= e(urlAplicacion('tienda')) ?>" method="post">
                                            <?= SesionSegura::campoCsrf() ?>
                                            <input type="hidden" name="accion" value="comprar" />
                                            <input type="hidden" name="id_skin" value="<?= (int) $skin['IDSkin'] ?>" />
                                            <input type="hidden" name="id_ficha" value="<?= (int) $skin['IDFicha'] ?>" />
                                            <button class="tienda-boton" type="submit" <?= $errorInventario !== '' || $saldo < $precio ? 'disabled' : '' ?>>
                                                <?= $errorInventario !== '' ? 'No disponible' : ($saldo < $precio ? 'Faltan puntos' : 'Comprar') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($catalogo === []): ?>
                        <p class="tienda-vacia">No hay skins disponibles en la tienda.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/preferencias.js"></script>
    <script src="js/traduccion.js"></script>
</body>

</html>
