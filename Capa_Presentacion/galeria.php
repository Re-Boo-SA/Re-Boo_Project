<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

$idUsuario = (int) $_SESSION['usuario_id'];
$skins = (new FachadaLogica())->retornoISkinsLogica();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['equipar'])) {
    $idFicha = filter_var($_POST['id_ficha'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
    $idSkin = filter_var($_POST['id_skin'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($idFicha === false || $idSkin === false) {
        mostrarPaginaError(400);
    }
    $resultado = $skins->equipar($idUsuario, $idFicha, $idSkin);
    SesionSegura::flash('flash_galeria', $resultado['mensaje']);
    header('Location: ' . urlAplicacion('galeria') . '#ficha-' . $idFicha);
    exit();
}

$mensaje = SesionSegura::tomarFlash('flash_galeria');
$catalogo = $skins->listarCatalogo();
$propias = array_map(fn($s) => $s['IDSkin'] . '-' . $s['IDFicha'], $skins->listarInventario($idUsuario));
$equipadas = $skins->listarEquipadas($idUsuario);

// Contenido de la galería: regla de juego y un dato histórico verificable por ficha.
$fichas = [
    1 => ['General', 'Militares', 'GeneralDraftDerMauer.png',
        'Después de 1945, Berlín quedó bajo el mando de los cuatro aliados (Estados Unidos, Reino Unido, Francia y la Unión Soviética), que la gobernaban a través de la Kommandatura aliada. Los soviéticos se retiraron de ella en 1948, en plena crisis del bloqueo.'],
    2 => ['Oficial Militar', 'Militares', 'OficialDraftDerMauer.png',
        'El 27 y 28 de octubre de 1961, tanques estadounidenses y soviéticos quedaron frente a frente en Checkpoint Charlie durante unas 16 horas, en uno de los momentos más tensos de la Guerra Fría.'],
    3 => ['Tropa', 'Militares', 'TropaDraftDerMauer.png',
        'Las Tropas de Frontera de la RDA (Grenztruppen) vigilaban el Muro con torres, perros y la "franja de la muerte", una zona despejada entre dos paredes donde se disparaba a quien intentara cruzar.'],
    4 => ['Cabo', 'Obreros', 'ACaboDraftDerMauer.png',
        'En la madrugada del 13 de agosto de 1961, obreros y soldados tendieron en pocas horas las primeras alambradas que cortaron la ciudad. En los días siguientes empezaron a reemplazarlas por bloques de hormigón.'],
    5 => ['Oficial Obrera', 'Obreros', 'OficialMujerDraftDerMauer.png',
        'La RDA impulsó el trabajo femenino en la industria: hacia los años ochenta, la gran mayoría de las mujeres en edad de trabajar tenía empleo, con guarderías estatales que lo hacían posible.'],
    6 => ['Peón', 'Obreros', 'PeonDraftDerMauer.png',
        'El levantamiento del 17 de junio de 1953 empezó con obreros de la construcción de la Stalinallee de Berlín Oriental, que protestaban contra el aumento de las normas de trabajo. Fue reprimido con tanques soviéticos.'],
    7 => ['Político', 'Políticos', 'PoliticoDraftDerMauer.png',
        'Muchas decisiones sobre Berlín se tomaron en discursos y conferencias de prensa: el 9 de noviembre de 1989, un anuncio confuso del vocero del gobierno de la RDA sobre los nuevos permisos de viaje llevó a miles de personas a los pasos fronterizos esa misma noche, y el Muro se abrió.'],
    8 => ['Markus', 'Espías', 'MarkusEspiaDraftDerMauer.png',
        'Berlín fue la capital del espionaje de la Guerra Fría. En el puente de Glienicke, entre Berlín Occidental y Potsdam, el Este y el Oeste intercambiaron espías detenidos; el primer canje fue en 1962.'],
    9 => ['Ursula', 'Espías', 'UrsulaEspiaDraftDerMauer.png',
        'La Stasi, la policía secreta de la RDA, llegó a tener unos 91.000 empleados en 1989, además de una extensa red de informantes no oficiales que vigilaban a vecinos, compañeros y familiares.'],
];
$reglas = [
    'Militares' => 'Van en Militares. Ahí valen 1, 3 y 7 según el orden en que aparece cada tipo distinto.',
    'Obreros' => 'Van en Fábrica. Ahí valen 1, 3 y 7 según el orden en que aparece cada tipo distinto.',
    'Políticos' => 'Cada uno suma 1 punto esté donde esté, pero fuera de su recinto solo cuando ya hay uno en Políticos.',
    'Espías' => 'Van en Espías. Markus y Ursula juntos suman 4 puntos.',
];
$skinsPorFicha = [];
foreach ($catalogo as $s) {
    $skinsPorFicha[(int) $s['IDFicha']][] = $s;
}
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Galería</title>
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
        <h1>Galería de fichas</h1>
        <p class="bajada">Cada ficha, dónde puntúa y un poco de la historia que hay detrás. Elegí la apariencia con la que la vas a usar en tus partidas.</p>
        <?php if ($mensaje !== ''): ?>
            <p class="mensaje" role="status"><?= e($mensaje) ?></p>
        <?php endif; ?>

        <div class="galeria">
            <?php foreach ($fichas as $idFicha => [$nombre, $categoria, $imagen, $historia]): ?>
                <article class="caja ficha-galeria" id="ficha-<?= $idFicha ?>">
                    <img src="img/fichas/<?= e($imagen) ?>" alt="<?= e($nombre) ?>">
                    <div>
                        <h2><?= e($nombre) ?></h2>
                        <p class="categoria"><?= e($categoria) ?></p>
                        <p><?= e($historia) ?></p>
                        <p class="regla"><?= e($reglas[$categoria]) ?></p>
                    </div>
                    <div class="skins" aria-label="Apariencias de <?= e($nombre) ?>">
                        <form method="POST">
                            <?= SesionSegura::campoCsrf() ?>
                            <input type="hidden" name="equipar" value="1">
                            <input type="hidden" name="id_ficha" value="<?= $idFicha ?>">
                            <input type="hidden" name="id_skin" value="0">
                            <button type="submit" class="opcion-skin <?= !isset($equipadas[$idFicha]) ? 'equipada' : '' ?>">
                                <img src="img/fichas/<?= e($imagen) ?>" alt="">Original
                            </button>
                        </form>
                        <?php foreach ($skinsPorFicha[$idFicha] ?? [] as $s): ?>
                            <?php $propia = in_array($s['IDSkin'] . '-' . $s['IDFicha'], $propias, true); ?>
                            <?php if ($propia): ?>
                                <form method="POST">
                                    <?= SesionSegura::campoCsrf() ?>
                                    <input type="hidden" name="equipar" value="1">
                                    <input type="hidden" name="id_ficha" value="<?= $idFicha ?>">
                                    <input type="hidden" name="id_skin" value="<?= (int) $s['IDSkin'] ?>">
                                    <button type="submit" class="opcion-skin <?= ($equipadas[$idFicha] ?? 0) === (int) $s['IDSkin'] ? 'equipada' : '' ?>">
                                        <img src="<?= e($s['URL']) ?>" alt=""><?= e($s['NombreSkin']) ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a class="opcion-skin bloqueada" href="<?= e(urlAplicacion('tienda')) ?>" title="Comprala en la tienda">
                                    <img src="<?= e($s['URL']) ?>" alt=""><?= e($s['NombreSkin']) ?><br><?= (int) $s['Precio'] ?> pts
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
</body>
</html>
