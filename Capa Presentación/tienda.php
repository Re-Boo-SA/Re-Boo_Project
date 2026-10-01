<?php
session_start();
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');
if (($_SESSION['rol'] ?? '') !== 'jugador') { header('Location: login.php'); exit(); }

$fachada = new FachadaLogica();
$idUsuario = (int) $_SESSION['usuario_id'];
$jugador = $fachada->retornoIJugadorLogica()->obtenerJugador($idUsuario);
$skinsLogica = $fachada->retornoISkinsLogica();
$mensaje = '';
$tipoMensaje = 'aviso-info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idSkin = (int) ($_POST['id_skin'] ?? 0);
    $idFicha = (int) ($_POST['id_ficha'] ?? 0);
    if (($_POST['accion'] ?? '') === 'comprar') {
        $resultado = $skinsLogica->comprarSkin($idUsuario, $idSkin, $idFicha);
        $mensaje = $resultado['mensaje'];
        $tipoMensaje = $resultado['exito'] ? 'aviso-ok' : 'aviso-error';
    }
    $jugador = $fachada->retornoIJugadorLogica()->obtenerJugador($idUsuario);
}

$catalogo = $skinsLogica->listarCatalogo();
$inventario = $skinsLogica->listarInventario($idUsuario);
$posee = [];
foreach ($inventario as $item) { $posee[$item['IDSkin'] . '-' . $item['IDFicha']] = true; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda | Draft der Mauer</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body class="tienda-body">
<header class="tienda-barra">
    <a href="inicioHome.php" class="tienda-marca"><span>D</span> Draft der Mauer</a>
    <nav class="tienda-enlaces"><a href="inicioHome.php">Inicio</a><a href="logout.php">Salir</a></nav>
</header>
<main class="tienda-contenido">
    <p class="tienda-titulo-pagina">TIENDA</p>
    <?php if ($mensaje): ?><div class="tienda-aviso <?= $tipoMensaje === 'aviso-error' ? 'error' : '' ?>"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <section class="tienda-panel">
        <header class="tienda-cabecera-panel"><div class="tienda-icono">▣</div><h1>TIENDA</h1><p>SKINS</p><div class="tienda-divisor"></div><div class="tienda-saldo"><small>Saldo disponible</small><strong><?= $jugador ? $jugador->getPntsPartida() : 0 ?> pts</strong></div></header>
        <div class="tienda-grid">
    <?php foreach ($catalogo as $skin): $clave = $skin['IDSkin'] . '-' . $skin['IDFicha']; $comprada = array_key_exists($clave, $posee); ?>
        <article class="skin-card">
            <div class="skin-imagen">
                <?php if (!empty($skin['URL']) && $skin['URL'] !== 'skin.png'): ?><img src="<?= htmlspecialchars($skin['URL'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($skin['NombreSkin'], ENT_QUOTES, 'UTF-8') ?>"><?php else: ?><span class="skin-silueta">♟</span><?php endif; ?>
                <span class="skin-especie"><?= htmlspecialchars(ucfirst($skin['FichaEspecie'])) ?></span>
            </div>
            <div class="skin-info"><h2><?= htmlspecialchars($skin['NombreSkin']) ?></h2><div class="skin-pie"><strong class="skin-precio">◉ <?= number_format((int) $skin['Precio'], 0, ',', '.') ?></strong>
                <?php if ($comprada): ?><span class="skin-comprada">Comprada</span><?php else: ?><form method="post"><input type="hidden" name="accion" value="comprar"><input type="hidden" name="id_skin" value="<?= (int) $skin['IDSkin'] ?>"><input type="hidden" name="id_ficha" value="<?= (int) $skin['IDFicha'] ?>"><button class="tienda-boton" type="submit">Comprar</button></form><?php endif; ?>
            </div></div>
        </article>
    <?php endforeach; ?>
    <?php if (empty($catalogo)): ?><p class="tienda-vacia">No hay skins disponibles en la tienda.</p><?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
