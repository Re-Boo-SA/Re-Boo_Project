<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

/*
 * RF27: formulario para reportar un problema del juego o denunciar a un jugador.
 * Se puede abrir desde el inicio o desde una partida (?partida=ID).
 */
$idUsuario = (int) $_SESSION['usuario_id'];
$fachada = new FachadaLogica();
$logica = $fachada->retornoIReporteLogica();
$partidas = $fachada->retornoIPartidaLogica();

$valores = [
    'tipo' => in_array($_GET['tipo'] ?? '', ReporteLogica::TIPOS, true) ? $_GET['tipo'] : 'bug',
    'motivo' => '',
    'descripcion' => '',
    'jugador' => is_string($_GET['jugador'] ?? null) ? mb_substr($_GET['jugador'], 0, 30) : '',
    'partida' => filter_var($_GET['partida'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null,
];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!SesionSegura::dentroDelLimite('reporte_form', 10, 60)) {
        mostrarPaginaError(429);
    }
    $valores['tipo'] = is_string($_POST['tipo'] ?? null) ? $_POST['tipo'] : '';
    $valores['motivo'] = is_string($_POST['motivo'] ?? null) ? $_POST['motivo'] : '';
    $valores['descripcion'] = is_string($_POST['descripcion'] ?? null) ? $_POST['descripcion'] : '';
    $valores['jugador'] = is_string($_POST['jugador'] ?? null) ? $_POST['jugador'] : '';
    $valores['partida'] = filter_var($_POST['partida'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;

    $resultado = $logica->enviar(
        $idUsuario,
        $valores['tipo'],
        $valores['motivo'],
        $valores['descripcion'],
        $valores['tipo'] === 'jugador' ? $valores['jugador'] : '',
        $valores['partida']
    );
    if ($resultado['exito']) {
        SesionSegura::flash('flash_reportes', $resultado['mensaje']);
        header('Location: ' . urlAplicacion('reportes'));
        exit();
    }
    $error = $resultado['mensaje'];
}

$mensaje = SesionSegura::tomarFlash('flash_reportes');
$misReportes = $logica->misReportes($idUsuario);

// Partidas que se pueden asociar al reporte: la que está en curso y las últimas terminadas.
$opcionesPartida = [];
$activa = $partidas->partidaActiva($idUsuario);
if ($activa !== null) {
    $opcionesPartida[$activa] = 'Partida en curso (#' . $activa . ')';
}
foreach ($partidas->historial($idUsuario, 10) as $p) {
    $opcionesPartida[(int) $p['IDPartida']] = $p['Nombre'] . ' (#' . (int) $p['IDPartida'] . ')';
}
if ($valores['partida'] !== null && !isset($opcionesPartida[$valores['partida']])) {
    $opcionesPartida[$valores['partida']] = 'Partida #' . $valores['partida'];
}

$nombresTipo = ['bug' => 'Problema del juego', 'jugador' => 'Jugador', 'chat' => 'Chat de Berlín'];
$nombresEstado = ['pendiente' => 'Pendiente', 'resuelto' => 'Resuelto', 'descartado' => 'Descartado'];
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Reportes</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/paginas.css">
</head>
<body class="cuerpo-pagina" data-idioma="<?= e($idioma) ?>">
    <header class="barra-pagina">
        <a class="marca" href="<?= e(urlAplicacion('inicio')) ?>">DRAFT DER MAUER</a>
        <a class="volver" href="<?= e(urlAplicacion('inicio')) ?>">Volver al inicio</a>
    </header>
    <main class="contenido contenido-angosto">
        <h1>Reportes</h1>
        <p class="bajada">Avisanos si algo del juego no funciona o si otro jugador se portó mal. Un administrador revisa cada reporte.</p>

        <?php if ($mensaje !== ''): ?>
            <p class="mensaje" role="status"><?= e($mensaje) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="mensaje mensaje-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="caja formulario-reporte" id="form-reporte" novalidate>
            <?= SesionSegura::campoCsrf() ?>
            <fieldset class="selector-tipo">
                <legend>¿Qué querés reportar?</legend>
                <label class="opcion-tipo">
                    <input type="radio" name="tipo" value="bug" <?= $valores['tipo'] !== 'jugador' ? 'checked' : '' ?>>
                    <span><strong>Un problema del juego</strong><small>Algo no anda, se ve mal o una regla no se aplicó bien</small></span>
                </label>
                <label class="opcion-tipo">
                    <input type="radio" name="tipo" value="jugador" <?= $valores['tipo'] === 'jugador' ? 'checked' : '' ?>>
                    <span><strong>A un jugador</strong><small>Insultos, trampas, nombre ofensivo o abandono a propósito</small></span>
                </label>
            </fieldset>

            <label class="campo" id="campo-jugador">
                <span>Nombre del jugador</span>
                <input type="text" name="jugador" maxlength="30" autocomplete="off" value="<?= e($valores['jugador']) ?>" placeholder="Tal como aparece en la partida">
            </label>

            <label class="campo">
                <span>Partida (opcional)</span>
                <select name="partida">
                    <option value="">No pasó en una partida</option>
                    <?php foreach ($opcionesPartida as $id => $texto): ?>
                        <option value="<?= (int) $id ?>" <?= $valores['partida'] === $id ? 'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="campo">
                <span>Asunto</span>
                <input type="text" name="motivo" required minlength="5" maxlength="120" value="<?= e($valores['motivo']) ?>" placeholder="Ej: El dado no se tiró en mi turno">
            </label>

            <label class="campo">
                <span>¿Qué pasó? <small class="contador" id="contador-descripcion" aria-live="polite"></small></span>
                <textarea name="descripcion" id="descripcion" required minlength="10" maxlength="1000" rows="6" placeholder="Contanos qué estabas haciendo y qué pasó. Si es un error, decinos qué esperabas que pasara."><?= e($valores['descripcion']) ?></textarea>
            </label>

            <button type="submit" class="boton boton-primario">Enviar reporte</button>
            <p class="nota">Podés mandar hasta <?= ReporteLogica::MAXIMO_POR_HORA ?> reportes por hora. Las denuncias falsas también se sancionan.</p>
        </form>

        <section class="caja mis-reportes" aria-labelledby="titulo-mis-reportes">
            <h2 id="titulo-mis-reportes">Mis reportes</h2>
            <?php if ($misReportes === []): ?>
                <p class="vacio">Todavía no mandaste ningún reporte.</p>
            <?php else: ?>
                <ul class="lista-reportes">
                    <?php foreach ($misReportes as $r): ?>
                        <li>
                            <div>
                                <strong>#<?= (int) $r['IDReportes'] ?> · <?= e($r['Motivo']) ?></strong>
                                <small>
                                    <?= e($nombresTipo[$r['Tipo']] ?? $r['Tipo']) ?>
                                    <?php if ($r['Denunciado'] !== null): ?> · <?= e($r['Denunciado']) ?><?php endif; ?>
                                    · <?= e(substr((string) $r['Fecha'], 0, 16)) ?>
                                </small>
                            </div>
                            <span class="estado-reporte <?= e($r['Estado']) ?>"><?= e($nombresEstado[$r['Estado']] ?? $r['Estado']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/reportes.js"></script>
    <script src="js/traduccion.js"></script>
</body>
</html>
