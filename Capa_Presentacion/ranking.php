<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

$idUsuario = (int) $_SESSION['usuario_id'];
$orden = is_string($_GET['orden'] ?? null) ? $_GET['orden'] : 'mmr';
$datos = (new FachadaLogica())->retornoIRankingLogica()->tabla($orden, $idUsuario);
$orden = $datos['orden'];
$pestanas = [
    'mmr' => 'Competitivo (MMR)',
    'historicos' => 'Puntos históricos',
    'victorias' => 'Victorias',
    'porcentaje' => '% de victorias',
];
$descripciones = [
    'mmr' => 'El nivel del modo competitivo. Sube al ganarle a rivales y baja al perder o abandonar.',
    'historicos' => 'Todos los puntos ganados en partidas normales y competitivas sin bots. Comprar skins no los baja.',
    'victorias' => 'Partidas ganadas en total.',
    'porcentaje' => 'Victorias sobre partidas jugadas. Aparecen quienes jugaron al menos ' . PersistenciaRanking::MINIMO_PARA_PORCENTAJE . ' partidas.',
];
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Ranking</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/paginas.css">
</head>
<body class="cuerpo-pagina" data-idioma="<?= e($idioma) ?>">
    <header class="barra-pagina">
        <a class="marca" href="<?= e(urlAplicacion('inicio')) ?>">DRAFT DER MAUER</a>
        <a class="volver" href="<?= e(urlAplicacion('inicio')) ?>">Volver al inicio</a>
    </header>
    <main class="contenido">
        <h1>Ranking global</h1>
        <p class="bajada"><?= e($descripciones[$orden]) ?></p>

        <nav class="pestanas" aria-label="Ordenar el ranking">
            <?php foreach ($pestanas as $clave => $nombre): ?>
                <a href="<?= e(urlAplicacion('ranking')) ?>?orden=<?= e($clave) ?>" <?= $clave === $orden ? 'aria-current="page"' : '' ?>><?= e($nombre) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($datos['mia'] !== null): ?>
            <section class="caja mi-posicion" aria-label="Tu posición">
                <span class="numero-grande"><?= (int) $datos['mia']['Puesto'] ?>°</span>
                <span>Tu puesto · <?= e($datos['mia']['rango']['icono'] . ' ' . $datos['mia']['rango']['nombre']) ?> · <?= (int) $datos['mia']['MMR'] ?> MMR</span>
            </section>
        <?php elseif ($orden === 'porcentaje'): ?>
            <p class="caja">Jugá al menos <?= PersistenciaRanking::MINIMO_PARA_PORCENTAJE ?> partidas para aparecer en esta tabla.</p>
        <?php endif; ?>

        <section class="caja">
            <?php if ($datos['filas'] === []): ?>
                <p class="vacio">Todavía no hay nadie en esta tabla.</p>
            <?php else: ?>
                <table class="tabla-ranking">
                    <thead>
                        <tr>
                            <th>Puesto</th><th>Jugador</th><th class="opcional">Rango</th>
                            <th class="<?= $orden === 'mmr' ? 'ordenada' : '' ?>">MMR</th>
                            <th class="<?= $orden === 'historicos' ? 'ordenada' : '' ?>">Puntos</th>
                            <th class="<?= $orden === 'victorias' ? 'ordenada' : '' ?>">Victorias</th>
                            <th class="opcional">Jugadas</th>
                            <th class="<?= $orden === 'porcentaje' ? 'ordenada' : '' ?>">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['filas'] as $f): ?>
                            <tr class="<?= (int) $f['IDUsuario'] === $idUsuario ? 'soy-yo' : '' ?>">
                                <td class="puesto"><?= (int) $f['Puesto'] ?></td>
                                <td><?= e($f['NombreUsuario']) ?></td>
                                <td class="opcional"><?= e($f['rango']['icono'] . ' ' . $f['rango']['nombre']) ?></td>
                                <td><?= (int) $f['MMR'] ?></td>
                                <td><?= (int) $f['PuntosHistoricos'] ?></td>
                                <td><?= (int) $f['PartidasGanadas'] ?></td>
                                <td class="opcional"><?= (int) $f['PartidasJugadas'] ?></td>
                                <td><?= e((string) $f['Porcentaje']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
</body>
</html>
