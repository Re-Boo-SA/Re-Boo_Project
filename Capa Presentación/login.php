<?php
// reCAPTCHA necesita permisos extra en la CSP solo en esta página
define('PAGINA_USA_RECAPTCHA', true);
require_once(__DIR__ . '/Seguridad/arranque.php');

if (isset($_POST['cambiar_idioma'])) {
    $_SESSION['idioma'] = ($_POST['idioma'] ?? '') === 'en' ? 'en' : 'es';
    header('Location: login');
    exit();
}
$idiomaActual = $_SESSION['idioma'] ?? 'es';

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');
require_once(__DIR__ . '/../Capa Lógica/SeguridadLogica.php');
require_once(__DIR__ . '/../config.php');

if (SesionSegura::estaAutenticado()) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: panel-admin');
        exit();
    } else {
        header('Location: inicio');
        exit();
    }
}

$mensajeResultado = '';
$identificador = '';
$resultado = null;
$recaptchaSiteKey = SeguridadLogica::obtenerClavePublicaRecaptcha();
$mensajeFlash = SesionSegura::tomarFlash('flash_error');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['cambiar_idioma'])) {
    $identificador = is_string($_POST['usuario'] ?? null) ? $_POST['usuario'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    try {
        $recaptchaValido = SeguridadLogica::verificarRecaptcha(
            is_string($_POST['recaptcha_token'] ?? null) ? $_POST['recaptcha_token'] : '',
            'login'
        );

        if (!$recaptchaValido) {
            $resultado = [
                'exito' => false,
                'mensaje' => 'No se pudo validar la seguridad del formulario. Intenta nuevamente.'
            ];
        } else {
            $fachadaLogica = new FachadaLogica();
            $usuarioLogica = $fachadaLogica->retornoIUsuarioLogica();
            $resultado = $usuarioLogica->iniciarSesion($identificador, $password);
        }
    } catch (RuntimeException $e) {
        error_log('Login: ' . $e->getMessage());
        $resultado = [
            'exito' => false,
            'mensaje' => 'La seguridad del servidor no está configurada correctamente.'
        ];
    }

    if (!$resultado['exito']) {
        $mensajeResultado = $resultado['mensaje'];
    } else {
        SesionSegura::iniciarSesionUsuario($resultado['usuario'], $resultado['rol']);
        header('Location: ' . $resultado['redirect']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Login</title>
    <link rel="icon" type="image/png" href="./img/imgPestaña/LogoBabyDraftDerMauer.png" />
    <link rel="stylesheet" href="./css/estilo.css" />
    <link rel="stylesheet" href="./css/fuentes.css" />
    <?php if ($recaptchaSiteKey !== ''): ?>
        <script
            src="https://www.recaptcha.net/recaptcha/api.js?render=<?= e($recaptchaSiteKey) ?>"></script>
    <?php endif; ?>
</head>

<body data-idioma="<?= e($idiomaActual) ?>">
    <!-- Banderas laterales de fondo -->
    <div class="bandera-izquierda"></div>
    <div class="bandera-derecha"></div>

    <div class="contenedor-app">
        <div class="pantalla">
            <header class="encabezado">
                <div class="contenido-encabezado">
                    <h1 class="titulo">DRAFT DER MAUER</h1>
                    <p class="subtitulo">ACCESO DE JUGADOR(A)</p>
                </div>
            </header>
            
            <div class="contenedor-pestanias">
                <div class="envoltorio-pestanias">
                    <a href="login" class="boton-pestania activo">Ingresar</a>
                    <a href="registro" class="boton-pestania">Crear cuentarda</a>
                </div>
            </div>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario">
                    <h2 class="titulo-formulario">INICIAR SESIÓN</h2>

                    <?php if ($mensajeFlash !== '' && $resultado === null): ?>
                        <div class="alerta alerta-error" id="alerta-login-flash">
                            <span><?= e($mensajeFlash) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($resultado !== null && !$resultado['exito']): ?>
                        <div class="alerta alerta-error" id="alerta-login-error">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            <span><?= htmlspecialchars($resultado['mensaje']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($resultado !== null && $resultado['exito']): ?>
                        <div class="alert alert-success" id="alert-login-success">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span><?= htmlspecialchars($resultado['mensaje']) ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="login-form" class="formulario" action="login" method="POST">
                        <?= SesionSegura::campoCsrf() ?>
                        <input type="hidden" name="recaptcha_token" id="recaptcha-token-login" value="" />
                        <div class="grupo-entrada">
                            <label for="usuario-ingreso">Nombre de usuariardo o email</label>
                            <input type="text" id="usuario-ingreso" name="usuario" placeholder="Ingresá tu usuario"
                                value="<?= e($identificador) ?>" required maxlength="100"
                                autocomplete="username" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="pass-ingreso">Contraseñarda</label>
                            <input type="password" id="pass-ingreso" name="password" placeholder="Ingresá tu contraseña"
                                required maxlength="128" autocomplete="current-password" />
                        </div>

                        <button type="submit" class="boton-enviar" id="btn-login-submit">
                            Ingresar
                        </button>

                        <p class="texto-cambiar">
                            ¿No tenés cuentarda todavía?
                            <a href="registro" class="boton-enlace">Registrate acá</a>
                        </p>
                    </form>
                </div>
            </main>

            <!-- Selector de Idioma -->
            <div class="contenedor-idioma">
                <form method="POST" id="form-idioma-login">
                    <?= SesionSegura::campoCsrf() ?>
                    <label for="select-idioma-login" class="label-idioma">Idioma</label>
                    <div class="select-personalizado">
                        <select name="idioma" id="select-idioma-login" data-autoenviar>
                            <option value="es" <?= $idiomaActual === 'es' ? 'selected' : '' ?>>ESP</option>
                            <option value="en" <?= $idiomaActual === 'en' ? 'selected' : '' ?>>ENG</option>
                        </select>
                        <span class="flecha-select">▼</span>
                    </div>
                    <input type="hidden" name="cambiar_idioma" value="1">
                </form>
            </div>
        </div>
    </div>

    <script src="./js/comun.js"></script>
    <script src="./js/traduccion.js"></script>

<?php if ($recaptchaSiteKey !== ''): ?>
    <script nonce="<?= CabecerasSeguridad::nonce() ?>">
        const loginForm = document.getElementById('login-form');
        let loginEnviado = false;

        loginForm.addEventListener('submit', function (evento) {
            if (loginEnviado) {
                return;
            }

            evento.preventDefault();

            if (typeof grecaptcha === 'undefined') {
                alert('No se pudo cargar reCAPTCHA. Recarga la página e inténtalo nuevamente.');
                return;
            }

            grecaptcha.ready(function () {
                grecaptcha.execute(<?= json_encode($recaptchaSiteKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, { action: 'login' })
                    .then(function (token) {
                        if (typeof token !== 'string' || token.trim() === '') {
                            alert('reCAPTCHA se ejecutó, pero Google devolvió un token vacío. Verifica que la clave sea de tipo v3 y que localhost esté autorizado.');
                            return;
                        }

                        document.getElementById('recaptcha-token-login').value = token;
                        loginEnviado = true;
                        loginForm.submit();
                    })
                    .catch(function () {
                        alert('Google no pudo validar reCAPTCHA. Revisa la configuración del dominio.');
                    });
            });
        });
    </script>
<?php endif; ?>
</body>

</html>