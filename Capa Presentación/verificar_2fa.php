<?php
// Segundo paso del login: la contraseña ya fue correcta, falta el código de la app autenticadora.
// La sesión de verdad recién se crea cuando este código es correcto.
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if (SesionSegura::estaAutenticado()) {
    header('Location: index.php');
    exit();
}

const MINUTOS_PARA_INGRESAR_CODIGO = 5;
const INTENTOS_2FA = 5;

function cancelarVerificacion(string $mensaje): never
{
    unset($_SESSION['2fa_pendiente_id'], $_SESSION['2fa_pendiente_rol'], $_SESSION['2fa_pendiente_desde'], $_SESSION['2fa_intentos']);
    SesionSegura::flash('flash_error', $mensaje);
    header('Location: login.php');
    exit();
}

$idPendiente = (int) ($_SESSION['2fa_pendiente_id'] ?? 0);
$rolPendiente = (string) ($_SESSION['2fa_pendiente_rol'] ?? '');

if ($idPendiente <= 0) {
    header('Location: login.php');
    exit();
}

if (time() - (int) ($_SESSION['2fa_pendiente_desde'] ?? 0) > MINUTOS_PARA_INGRESAR_CODIGO * 60) {
    cancelarVerificacion('Pasó demasiado tiempo. Volvé a iniciar sesión.');
}

$fachada = new FachadaLogica();
$auditoria = $fachada->retornoIAuditoriaLogica();
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = is_string($_POST['codigo'] ?? null) ? mb_substr($_POST['codigo'], 0, 20) : '';
    $_SESSION['2fa_intentos'] = (int) ($_SESSION['2fa_intentos'] ?? 0) + 1;

    if ($fachada->retornoIDosFactoresLogica()->verificarLogin($idPendiente, $codigo)) {
        $usuario = $fachada->retornoIUsuarioLogica()->cargarUsuario($idPendiente, $rolPendiente);

        if ($usuario === null) {
            cancelarVerificacion('Tu cuenta ya no tiene acceso.');
        }

        $auditoria->registrar('login_exitoso', 'info', $idPendiente, null, 'Con verificación en dos pasos');
        SesionSegura::iniciarSesionUsuario($usuario, $rolPendiente);
        header('Location: ' . ($rolPendiente === 'administrador' ? 'panel_admin.php' : 'inicioHome.php'));
        exit();
    }

    // Cuenta también como login fallido: así el bloqueo por fuerza bruta abarca los dos pasos
    $auditoria->registrar('2fa_fallido', 'aviso', $idPendiente);
    $auditoria->registrar('login_fallido', 'aviso', $idPendiente, null, 'Código 2FA incorrecto');

    if ($_SESSION['2fa_intentos'] >= INTENTOS_2FA) {
        cancelarVerificacion('Demasiados códigos incorrectos. Volvé a iniciar sesión.');
    }

    $mensajeError = 'El código no es correcto.';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Verificación en dos pasos</title>
    <link rel="stylesheet" href="./css/fuentes.css" />
    <link rel="stylesheet" href="./css/estilo.css" />
</head>

<body data-idioma="<?= e($_SESSION['idioma'] ?? 'es') ?>">
    <div class="bandera-izquierda"></div>
    <div class="bandera-derecha"></div>

    <div class="contenedor-app">
        <div class="pantalla">
            <header class="encabezado">
                <div class="contenido-encabezado">
                    <h1 class="titulo">DRAFT DER MAUER</h1>
                    <p class="subtitulo">VERIFICACIÓN EN DOS PASOS</p>
                </div>
            </header>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario">
                    <h2 class="titulo-formulario">INGRESÁ EL CÓDIGO</h2>

                    <?php if ($mensajeError !== ''): ?>
                        <div class="alerta alerta-error">
                            <span><?= e($mensajeError) ?></span>
                        </div>
                    <?php endif; ?>

                    <form class="formulario" action="verificar_2fa.php" method="POST">
                        <?= SesionSegura::campoCsrf() ?>
                        <div class="grupo-entrada">
                            <label for="codigo-2fa">Código de 6 dígitos de tu app autenticadora, o un código de recuperación</label>
                            <input type="text" id="codigo-2fa" name="codigo" required maxlength="20"
                                inputmode="numeric" autocomplete="one-time-code" autofocus />
                        </div>

                        <button type="submit" class="boton-enviar">Verificar</button>

                        <p class="texto-cambiar">
                            <a href="login.php" class="boton-enlace">Volver al inicio de sesión</a>
                        </p>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="./js/comun.js"></script>
    <script src="./js/traduccion.js"></script>
</body>

</html>
