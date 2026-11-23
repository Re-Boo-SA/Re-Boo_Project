<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

$idUsuario = (int) $_SESSION['usuario_id'];
$fachada = new FachadaLogica();
$jugador = $fachada->retornoIJugadorLogica()->obtenerJugador($idUsuario);
$mmr = $jugador ? $jugador->getMMR() : RankingLogica::MMR_INICIAL;
$rango = RankingLogica::rango($mmr);
$partidaActiva = $fachada->retornoIPartidaLogica()->partidaActiva($idUsuario);
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Competitivo</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/paginas.css">
</head>
<body class="cuerpo-pagina" data-idioma="<?= e($idioma) ?>"
      data-api="<?= e(urlAplicacion('api-cola')) ?>"
      data-partida="<?= e(urlAplicacion('partida')) ?>"
      data-csrf="<?= e(SesionSegura::tokenCsrf()) ?>">
    <header class="barra-pagina">
        <a class="marca" href="<?= e(urlAplicacion('inicio')) ?>">DRAFT DER MAUER</a>
        <a class="volver" href="<?= e(urlAplicacion('inicio')) ?>">Volver al inicio</a>
    </header>
    <main class="contenido">
        <h1>Competitivo</h1>
        <p class="bajada">Te emparejamos con rivales de MMR parecido. Reglas oficiales, sin bots, y tu MMR sube o baja según el resultado.</p>

        <?php if ($partidaActiva !== null): ?>
            <p class="mensaje">Estás en una partida. <a href="<?= e(urlAplicacion('partida')) ?>?id=<?= (int) $partidaActiva ?>">Volver a la partida</a> antes de buscar otra.</p>
        <?php endif; ?>

        <div class="competitivo">
            <section class="caja tarjeta-rango">
                <span class="icono-rango" aria-hidden="true"><?= e($rango['icono']) ?></span>
                <h2><?= e($rango['nombre']) ?></h2>
                <span class="numero-grande"><?= (int) $mmr ?></span>
                <span>MMR</span>
                <ol class="escalera" aria-label="Rangos">
                    <?php foreach (array_reverse(RankingLogica::RANGOS) as $r): ?>
                        <li class="<?= $r['nombre'] === $rango['nombre'] ? 'actual' : '' ?>"><?= e($r['icono'] . ' ' . $r['nombre']) ?>, desde <?= (int) $r['minimo'] ?></li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <section class="caja busqueda">
                <h2>Buscar partida</h2>
                <div id="buscando" hidden>
                    <div class="radar" aria-hidden="true"></div>
                    <p class="dato-cola" id="dato-tiempo">Buscando rivales…</p>
                    <p class="dato-cola" id="dato-cola"></p>
                </div>
                <button type="button" class="boton boton-primario" id="btn-buscar" <?= $partidaActiva !== null ? 'disabled' : '' ?>>Buscar partida</button>
                <button type="button" class="boton" id="btn-cancelar" hidden>Dejar de buscar</button>
                <ul class="reglas-competitivo">
                    <li>Se arman mesas de 4. Si esperás más de un minuto, se arma con 2 o 3.</li>
                    <li>El margen de MMR aceptado crece mientras esperás.</li>
                    <li>Abandonar cuenta como último puesto y resta 15 de MMR extra.</li>
                </ul>
            </section>
        </div>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
    <script src="js/competitivo.js"></script>
</body>
</html>
