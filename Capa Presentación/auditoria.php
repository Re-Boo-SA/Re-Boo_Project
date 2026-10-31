<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

SesionSegura::exigirRol('administrador');

$tiposEvento = [
    'login_exitoso', 'login_fallido', 'login_bloqueado', 'cuenta_bloqueada', 'logout',
    'registro', 'registro_limitado', 'bot_detectado', 'sesion_expirada', 'sesion_revocada',
    'acceso_denegado', 'csrf_invalido', 'perfil_modificado', 'foto_subida', 'foto_rechazada',
    'partida_creada', 'unirse_partida', '2fa_activado', '2fa_desactivado', '2fa_desactivar_fallido',
    '2fa_fallido', '2fa_codigo_recuperacion', 'auditoria_consultada'
];

$tipoElegido = in_array($_GET['tipo'] ?? '', $tiposEvento, true) ? $_GET['tipo'] : '';

$auditoria = (new FachadaLogica())->retornoIAuditoriaLogica();
$auditoria->registrar('auditoria_consultada', 'info', (int) $_SESSION['usuario_id'], null, $tipoElegido !== '' ? 'Filtro: ' . $tipoElegido : null);
$eventos = $auditoria->listar(200, $tipoElegido);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Auditoría</title>
    <link rel="stylesheet" href="./css/fuentes.css" />
    <link rel="stylesheet" href="./css/estilo.css" />
</head>

<body data-idioma="<?= e($_SESSION['idioma'] ?? 'es') ?>">
    <div class="contenedor-app contenedor-ancho">
        <div class="pantalla">
            <header class="encabezado">
                <div class="contenido-encabezado">
                    <h1 class="titulo">DRAFT DER MAUER</h1>
                    <p class="subtitulo">REGISTRO DE SEGURIDAD</p>
                </div>
            </header>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario tarjeta-ancha">
                    <form method="GET" action="auditoria.php" class="filtro-auditoria">
                        <label for="filtro-tipo">Tipo de evento</label>
                        <select name="tipo" id="filtro-tipo" data-autoenviar>
                            <option value="">Todos</option>
                            <?php foreach ($tiposEvento as $tipo): ?>
                                <option value="<?= e($tipo) ?>" <?= $tipo === $tipoElegido ? 'selected' : '' ?>><?= e($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span>Últimos <?= count($eventos) ?> eventos</span>
                    </form>

                    <div class="tabla-auditoria-contenedor">
                        <table class="tabla-auditoria">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Nivel</th>
                                    <th>Usuario</th>
                                    <th>Identificador</th>
                                    <th>IP</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventos as $evento): ?>
                                    <tr class="nivel-<?= e($evento['Nivel']) ?>">
                                        <td><?= e($evento['Fecha']) ?></td>
                                        <td><?= e($evento['Tipo']) ?></td>
                                        <td><?= e($evento['Nivel']) ?></td>
                                        <td><?= e($evento['IDUsuario'] === null ? '-' : (string) $evento['IDUsuario']) ?></td>
                                        <td><?= e($evento['Identificador']) ?></td>
                                        <td><?= e($evento['IP']) ?></td>
                                        <td><?= e($evento['Detalle']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <a href="panel_admin.php" class="boton-enviar boton-enlace-bloque">Volver al panel</a>
                </div>
            </main>
        </div>
    </div>

    <script src="./js/comun.js"></script>
    <script src="./js/traduccion.js"></script>
</body>

</html>
