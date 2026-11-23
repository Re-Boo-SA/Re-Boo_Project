<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('administrador');

$idAdmin = (int) $_SESSION['usuario_id'];
$nombreAdmin = $_SESSION['nombre_usuario'] ?? 'Administrador';
$correoAdmin = $_SESSION['correo'] ?? '';

$fachada = new FachadaLogica();
$reportes = $fachada->retornoIReporteLogica();

// RF26: cerrar un reporte (resolverlo o descartarlo).
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['cerrar_reporte'])) {
    $idReporte = filter_var($_POST['id_reporte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $estado = is_string($_POST['estado'] ?? null) ? $_POST['estado'] : '';
    if ($idReporte === false || $idReporte === null) {
        mostrarPaginaError(400);
    }
    $resultado = $reportes->cerrar($idAdmin, $idReporte, $estado);
    SesionSegura::flash('flash_admin', $resultado['mensaje']);
    $filtroActual = is_string($_POST['filtro'] ?? null) ? $_POST['filtro'] : 'pendiente';
    header('Location: ' . urlAplicacion('panel-admin') . '?reportes=' . rawurlencode($filtroActual) . '#reportes');
    exit();
}

$filtros = ['pendiente' => 'Pendientes', 'resuelto' => 'Resueltos', 'descartado' => 'Descartados', 'todos' => 'Todos'];
$filtro = isset($filtros[$_GET['reportes'] ?? '']) ? $_GET['reportes'] : 'pendiente';
$listaReportes = $reportes->listarParaAdmin($filtro === 'todos' ? '' : $filtro);
$conteo = $reportes->contarPorEstado();
$mensaje = SesionSegura::tomarFlash('flash_admin');

$jugadores = $fachada->retornoIJugadorLogica()->listarJugadores();
$admins = $fachada->retornoIAdminLogica()->listarAdmins();
$nombresTipo = ['bug' => 'Problema del juego', 'jugador' => 'Denuncia', 'chat' => 'Chat de Berlín'];
$nombresEstado = ['pendiente' => 'Pendiente', 'resuelto' => 'Resuelto', 'descartado' => 'Descartado'];
$idioma = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Der Mauer - Administración</title>
    <link rel="icon" type="image/png" href="./img/imgPestania/LogoBabyDraftDerMauer.png">
    <link rel="stylesheet" href="css/fuentes.css">
    <link rel="stylesheet" href="css/paginas.css">
</head>
<body class="cuerpo-pagina" data-idioma="<?= e($idioma) ?>">
    <header class="barra-pagina">
        <span class="marca">DRAFT DER MAUER · ADMIN</span>
        <form method="POST" action="<?= e(urlAplicacion('cerrar-sesion')) ?>" class="form-en-linea">
            <?= SesionSegura::campoCsrf() ?>
            <button type="submit" class="volver boton-enlace">Cerrar sesión</button>
        </form>
    </header>

    <main class="contenido">
        <h1>Panel de administración</h1>
        <p class="bajada"><?= e($nombreAdmin) ?><?= $correoAdmin !== '' ? ' · ' . e($correoAdmin) : '' ?></p>

        <?php if ($mensaje !== ''): ?>
            <p class="mensaje" role="status"><?= e($mensaje) ?></p>
        <?php endif; ?>

        <section class="tarjetas-datos" aria-label="Resumen">
            <div class="caja dato">
                <span>Jugadores registrados</span>
                <strong class="numero-grande"><?= count($jugadores) ?></strong>
            </div>
            <div class="caja dato">
                <span>Administradores</span>
                <strong class="numero-grande"><?= count($admins) ?></strong>
            </div>
            <a class="caja dato dato-enlace <?= $conteo['pendiente'] > 0 ? 'alerta' : '' ?>" href="#reportes">
                <span>Reportes pendientes</span>
                <strong class="numero-grande"><?= (int) $conteo['pendiente'] ?></strong>
            </a>
            <a class="caja dato dato-enlace" href="<?= e(urlAplicacion('auditoria')) ?>">
                <span>Registro de seguridad</span>
                <strong>Ver auditoría →</strong>
            </a>
        </section>

        <section class="caja seccion-admin" id="reportes" aria-labelledby="titulo-reportes">
            <h2 id="titulo-reportes">Reportes de jugadores</h2>
            <nav class="pestanas" aria-label="Filtrar reportes">
                <?php foreach ($filtros as $clave => $nombre): ?>
                    <a href="<?= e(urlAplicacion('panel-admin')) ?>?reportes=<?= e($clave) ?>#reportes" <?= $clave === $filtro ? 'aria-current="page"' : '' ?>>
                        <?= e($nombre) ?><?php if ($clave !== 'todos'): ?> (<?= (int) $conteo[$clave] ?>)<?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ($listaReportes === []): ?>
                <p class="vacio">No hay reportes en esta lista.</p>
            <?php else: ?>
                <ul class="lista-reportes lista-admin">
                    <?php foreach ($listaReportes as $r): ?>
                        <li class="reporte-admin">
                            <div class="reporte-cuerpo">
                                <div class="reporte-cabecera">
                                    <span class="etiqueta-tipo <?= e($r['Tipo']) ?>"><?= e($nombresTipo[$r['Tipo']] ?? $r['Tipo']) ?></span>
                                    <strong>#<?= (int) $r['IDReportes'] ?> · <?= e($r['Motivo']) ?></strong>
                                </div>
                                <?php if ($r['Descripcion'] !== null && $r['Descripcion'] !== ''): ?>
                                    <p class="reporte-descripcion"><?= nl2br(e($r['Descripcion'])) ?></p>
                                <?php endif; ?>
                                <small>
                                    <?= e(substr((string) $r['Fecha'], 0, 16)) ?>
                                    · De: <?= e($r['Reportante'] ?? 'sistema') ?>
                                    <?php if ($r['Denunciado'] !== null): ?> · Denunciado: <strong><?= e($r['Denunciado']) ?></strong> (#<?= (int) $r['IDDenunciado'] ?>)<?php endif; ?>
                                    <?php if ($r['IDPartida'] !== null): ?> · Partida: <?= e($r['NombrePartida'] ?? '') ?> (#<?= (int) $r['IDPartida'] ?>)<?php endif; ?>
                                    <?php if ($r['Estado'] !== 'pendiente'): ?> · <?= e($nombresEstado[$r['Estado']] ?? $r['Estado']) ?><?= $r['Admin'] !== null ? ' por ' . e($r['Admin']) : '' ?><?php endif; ?>
                                </small>
                            </div>
                            <?php if ($r['Estado'] === 'pendiente'): ?>
                                <div class="reporte-acciones">
                                    <?php foreach (['resuelto' => 'Resolver', 'descartado' => 'Descartar'] as $estado => $texto): ?>
                                        <form method="POST">
                                            <?= SesionSegura::campoCsrf() ?>
                                            <input type="hidden" name="cerrar_reporte" value="1">
                                            <input type="hidden" name="id_reporte" value="<?= (int) $r['IDReportes'] ?>">
                                            <input type="hidden" name="estado" value="<?= e($estado) ?>">
                                            <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
                                            <button type="submit" class="boton <?= $estado === 'resuelto' ? 'boton-primario' : '' ?>"><?= e($texto) ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="estado-reporte <?= e($r['Estado']) ?>"><?= e($nombresEstado[$r['Estado']] ?? $r['Estado']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="caja seccion-admin" aria-labelledby="titulo-jugadores">
            <h2 id="titulo-jugadores">Jugadores registrados</h2>
            <?php if ($jugadores === []): ?>
                <p class="vacio">No hay jugadores activos.</p>
            <?php else: ?>
                <div class="tabla-desplazable">
                    <table class="tabla-ranking tabla-admin">
                        <thead>
                            <tr><th>ID</th><th>Jugador</th><th class="opcional">Correo</th><th>Partidas</th><th>Ganadas</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jugadores as $j): ?>
                                <tr>
                                    <td>#<?= (int) $j->getIdUsuario() ?></td>
                                    <td><?= e($j->getNombreUsuario()) ?></td>
                                    <td class="opcional"><?= e($j->getCorreo()) ?></td>
                                    <td><?= (int) $j->getPartidasJugadas() ?></td>
                                    <td><?= (int) $j->getPartidasGanadas() ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <p class="nota">Por seguridad, los administradores no se registran desde la web: su alta se hace directamente en las tablas <code>USUARIOS</code> y <code>ADMINISTRADORES</code>.</p>
    </main>
    <script src="js/comun.js"></script>
    <script src="js/traduccion.js"></script>
</body>
</html>
