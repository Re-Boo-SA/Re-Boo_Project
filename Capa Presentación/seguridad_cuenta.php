<?php
// Activar o desactivar la verificación en dos pasos (2FA con TOTP) de la propia cuenta.
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

SesionSegura::exigirRol('jugador', 'administrador');

$idUsuario = (int) $_SESSION['usuario_id'];
$fachada = new FachadaLogica();
$dosFactores = $fachada->retornoIDosFactoresLogica();
$mensaje = '';
$exito = false;
$codigosNuevos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $codigo = is_string($_POST['codigo'] ?? null) ? mb_substr($_POST['codigo'], 0, 20) : '';

    if ($accion === 'iniciar' && !$dosFactores->estaActivo($idUsuario)) {
        // El secreto queda en la sesión hasta que el usuario demuestre que lo cargó en su app
        $_SESSION['2fa_nuevo_secreto'] = $dosFactores->generarSecreto();
        $_SESSION['2fa_intentos_activacion'] = 0;
    } elseif ($accion === 'confirmar' && isset($_SESSION['2fa_nuevo_secreto'])) {
        $_SESSION['2fa_intentos_activacion'] = (int) ($_SESSION['2fa_intentos_activacion'] ?? 0) + 1;

        if ($_SESSION['2fa_intentos_activacion'] > 10) {
            unset($_SESSION['2fa_nuevo_secreto']);
            $mensaje = 'Demasiados intentos. Empezá de nuevo.';
        } else {
            $resultado = $dosFactores->confirmarActivacion($idUsuario, $_SESSION['2fa_nuevo_secreto'], $codigo);
            $mensaje = $resultado['mensaje'];
            $exito = $resultado['exito'];
            if ($exito) {
                $codigosNuevos = $resultado['codigos'];
                unset($_SESSION['2fa_nuevo_secreto']);
            }
        }
    } elseif ($accion === 'cancelar') {
        unset($_SESSION['2fa_nuevo_secreto']);
    } elseif ($accion === 'desactivar') {
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $resultado = $dosFactores->desactivar($idUsuario, $password, $codigo);
        $mensaje = $resultado['mensaje'];
        $exito = $resultado['exito'];
    }
}

$activo = $dosFactores->estaActivo($idUsuario);
$secretoPendiente = $_SESSION['2fa_nuevo_secreto'] ?? null;
$paginaVolver = $_SESSION['rol'] === 'administrador' ? 'panel_admin.php' : 'inicioHome.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Seguridad de la cuenta</title>
    <link rel="stylesheet" href="./css/fuentes.css" />
    <link rel="stylesheet" href="./css/estilo.css" />
</head>

<body data-idioma="<?= e($_SESSION['idioma'] ?? 'es') ?>">
    <div class="contenedor-app">
        <div class="pantalla">
            <header class="encabezado">
                <div class="contenido-encabezado">
                    <h1 class="titulo">DRAFT DER MAUER</h1>
                    <p class="subtitulo">SEGURIDAD DE LA CUENTA</p>
                </div>
            </header>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario tarjeta-ancha">
                    <h2 class="titulo-formulario">VERIFICACIÓN EN DOS PASOS</h2>

                    <?php if ($mensaje !== ''): ?>
                        <div class="alerta <?= $exito ? 'alerta-exito' : 'alerta-error' ?>">
                            <span><?= e($mensaje) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($codigosNuevos !== []): ?>
                        <p class="texto-cambiar">Guardá estos códigos de recuperación en un lugar seguro. Cada uno sirve
                            una sola vez para entrar si perdés el celular. <strong>No se vuelven a mostrar.</strong></p>
                        <ul class="lista-codigos">
                            <?php foreach ($codigosNuevos as $codigoRecuperacion): ?>
                                <li><?= e($codigoRecuperacion) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($activo): ?>
                        <p class="texto-cambiar">Estado: <strong>activada</strong>. Te quedan
                            <?= $dosFactores->codigosRestantes($idUsuario) ?> códigos de recuperación.</p>

                        <form class="formulario" action="seguridad_cuenta.php" method="POST">
                            <?= SesionSegura::campoCsrf() ?>
                            <input type="hidden" name="accion" value="desactivar">
                            <div class="grupo-entrada">
                                <label for="pass-desactivar">Contraseña actual</label>
                                <input type="password" id="pass-desactivar" name="password" required maxlength="128" autocomplete="current-password" />
                            </div>
                            <div class="grupo-entrada">
                                <label for="codigo-desactivar">Código de la app</label>
                                <input type="text" id="codigo-desactivar" name="codigo" required maxlength="20" inputmode="numeric" autocomplete="one-time-code" />
                            </div>
                            <button type="submit" class="boton-enviar">Desactivar</button>
                        </form>

                    <?php elseif ($secretoPendiente !== null): ?>
                        <p class="texto-cambiar">1. Abrí Google Authenticator, Microsoft Authenticator o Authy y elegí
                            "Ingresar una clave de configuración". Cargá esta clave (tipo: basada en el tiempo):</p>
                        <div class="codigo-secreto"><?= e(trim(chunk_split($secretoPendiente, 4, ' '))) ?></div>
                        <p class="texto-cambiar">Si tu app acepta enlaces, también podés usar:</p>
                        <div class="codigo-secreto"><?= e($dosFactores->uriConfiguracion($secretoPendiente, $_SESSION['nombre_usuario'] ?? 'usuario')) ?></div>

                        <form class="formulario" action="seguridad_cuenta.php" method="POST">
                            <?= SesionSegura::campoCsrf() ?>
                            <input type="hidden" name="accion" value="confirmar">
                            <div class="grupo-entrada">
                                <label for="codigo-confirmar">2. Escribí el código de 6 dígitos que te muestra la app</label>
                                <input type="text" id="codigo-confirmar" name="codigo" required maxlength="20" inputmode="numeric" autocomplete="one-time-code" />
                            </div>
                            <button type="submit" class="boton-enviar">Activar</button>
                        </form>

                        <form action="seguridad_cuenta.php" method="POST">
                            <?= SesionSegura::campoCsrf() ?>
                            <input type="hidden" name="accion" value="cancelar">
                            <button type="submit" class="boton-enlace">Cancelar</button>
                        </form>

                    <?php else: ?>
                        <p class="texto-cambiar">Estado: <strong>desactivada</strong>. Con la verificación en dos pasos,
                            aunque alguien consiga tu contraseña no puede entrar sin el código de tu celular.</p>
                        <form class="formulario" action="seguridad_cuenta.php" method="POST">
                            <?= SesionSegura::campoCsrf() ?>
                            <input type="hidden" name="accion" value="iniciar">
                            <button type="submit" class="boton-enviar">Activar verificación en dos pasos</button>
                        </form>
                    <?php endif; ?>

                    <a href="<?= e($paginaVolver) ?>" class="boton-enviar boton-enlace-bloque">Volver</a>
                </div>
            </main>
        </div>
    </div>

    <script src="./js/comun.js"></script>
    <script src="./js/traduccion.js"></script>
</body>

</html>
