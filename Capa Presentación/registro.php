<?php
// reCAPTCHA necesita permisos extra en la CSP solo en esta página
define('PAGINA_USA_RECAPTCHA', true);
require_once(__DIR__ . '/Seguridad/arranque.php');

if (isset($_POST['cambiar_idioma'])) {
    $_SESSION['idioma'] = ($_POST['idioma'] ?? '') === 'en' ? 'en' : 'es';
    header('Location: registro.php');
    exit();
}
$idiomaActual = $_SESSION['idioma'] ?? 'es';

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');
require_once(__DIR__ . '/../Capa Lógica/SeguridadLogica.php');
require_once(__DIR__ . '/../config.php');

if (SesionSegura::estaAutenticado()) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: panel_admin.php');
        exit();
    } else {
        header('Location: inicioHome.php');
        exit();
    }
}

$mensajeResultado = '';
$resultado = null;
$viejo_nombre = '';
$viejo_correo = '';
$recaptchaSiteKey = SeguridadLogica::obtenerClavePublicaRecaptcha();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['cambiar_idioma'])) {
    $nombreUsuario = is_string($_POST['usuario'] ?? null) ? $_POST['usuario'] : '';
    $correo = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmarPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

    $fachadaLogica = new FachadaLogica();

    try {
        // Campo trampa (honeypot): está oculto, una persona nunca lo completa; un bot sí.
        if (($_POST['sitio_web'] ?? '') !== '') {
            $fachadaLogica->retornoIAuditoriaLogica()->registrar('bot_detectado', 'aviso', null, $correo, 'Honeypot del registro');
            $resultado = [
                'exito' => false,
                'mensaje' => 'No se pudo validar la seguridad del formulario. Intenta nuevamente.'
            ];
        } elseif (!SeguridadLogica::verificarRecaptcha(
            is_string($_POST['recaptcha_token'] ?? null) ? $_POST['recaptcha_token'] : '',
            'registro'
        )) {
            $resultado = [
                'exito' => false,
                'mensaje' => 'No se pudo validar la seguridad del formulario. Intenta nuevamente.'
            ];
        } else {
            $jugadorLogica = $fachadaLogica->retornoIJugadorLogica();
            $resultado = $jugadorLogica->registrarJugador(
                $nombreUsuario,
                $correo,
                $password,
                $confirmarPassword
            );
        }
    } catch (RuntimeException $e) {
        error_log('Registro: ' . $e->getMessage());
        $resultado = [
            'exito' => false,
            'mensaje' => 'La seguridad del servidor no está configurada correctamente.'
        ];
    }

    if (!$resultado['exito']) {
        $mensajeResultado = $resultado['mensaje'];
        $viejo_nombre = $nombreUsuario;
        $viejo_correo = $correo;
    } else {
        $mensajeResultado = $resultado['mensaje'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Registro</title>
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
                    <a href="login.php" class="boton-pestania">Ingresar</a>
                    <a href="registro.php" class="boton-pestania activo">Crear cuentarda</a>
                </div>
            </div>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario">
                    <h2 class="titulo-formulario">REGISTRARSE</h2>

                    <?php if ($resultado !== null && !$resultado['exito']): ?>
                        <div class="alerta alerta-error" id="alerta-registro-error">
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
                        <div class="alerta alerta-exito" id="alerta-registro-exito">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span><?= htmlspecialchars($resultado['mensaje']); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="registro-form" class="formulario" action="registro.php" method="POST">
                        <?= SesionSegura::campoCsrf() ?>
                        <!-- Campo trampa para bots: oculto con CSS y fuera del orden de tabulación -->
                        <div class="campo-trampa" aria-hidden="true">
                            <label for="sitio-web">No completar este campo</label>
                            <input type="text" id="sitio-web" name="sitio_web" value="" tabindex="-1" autocomplete="off" />
                        </div>
                        <input type="hidden" name="recaptcha_token" id="recaptcha-token-registro" value="" />
                        <div class="grupo-entrada">
                            <label for="usuario-registro">Usuariardo</label>
                            <input type="text" id="usuario-registro" name="usuario"
                                placeholder="Elegí un nombre de usuario ~😉"
                                value="<?= e($viejo_nombre) ?>" required minlength="3" maxlength="30"
                                autocomplete="username" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="email-registro">Correo electronico</label>
                            <input type="email" id="email-registro" name="email" placeholder="nombre@correo.com"
                                value="<?= e($viejo_correo) ?>" required maxlength="100"
                                autocomplete="email" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="pass-registro">Contraseñarda</label>
                            <input type="password" id="pass-registro" name="password"
                                placeholder="Ingresá una contraseñarda" required minlength="10" maxlength="128"
                                autocomplete="new-password" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="confirmar-pass">Confirmar contraseñarda</label>
                            <input type="password" id="confirmar-pass" name="confirm_password"
                                placeholder="Repetí la contraseñarda" required minlength="10" maxlength="128"
                                autocomplete="new-password" />
                        </div>

                        <button type="submit" class="boton-enviar" id="btn-registro-submit">
                            Crearme una cuentarda
                        </button>

                        <p class="texto-cambiar">
                            ¿Ya tenés cuentarda?
                            <a href="login.php" class="boton-enlace">Iniciá sesión</a>
                        </p>
                    </form>
                </div>
            </main>

            <!-- Selector de Idioma -->
            <div class="contenedor-idioma">
                <form method="POST" id="form-idioma-registro">
                    <?= SesionSegura::campoCsrf() ?>
                    <label for="select-idioma-registro" class="label-idioma">Idioma</label>
                    <div class="select-personalizado">
                        <select name="idioma" id="select-idioma-registro" data-autoenviar>
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
        const registroForm = document.getElementById('registro-form');
        let registroEnviado = false;

        registroForm.addEventListener('submit', function (evento) {
            if (registroEnviado) {
                return;
            }

            evento.preventDefault();

            if (typeof grecaptcha === 'undefined') {
                alert('No se pudo cargar reCAPTCHA. Recarga la página e inténtalo nuevamente.');
                return;
            }

            grecaptcha.ready(function () {
                grecaptcha.execute(<?= json_encode($recaptchaSiteKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, { action: 'registro' })
                    .then(function (token) {
                        if (typeof token !== 'string' || token.trim() === '') {
                            alert('reCAPTCHA se ejecutó, pero Google devolvió un token vacío. Verifica que la clave sea de tipo v3 y que localhost esté autorizado.');
                            return;
                        }

                        document.getElementById('recaptcha-token-registro').value = token;
                        registroEnviado = true;
                        registroForm.submit();
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