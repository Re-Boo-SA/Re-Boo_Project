<?php
session_start();

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: panel_admin.php');
        exit();
    }

    header('Location: inicioHome.php');
    exit();
}

$mensajeResultado = '';
$exitoRegistro = false;
$viejoNombre = '';
$viejoCorreo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $viejoNombre = $_POST['usuario'] ?? '';
    $viejoCorreo = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirm_password'] ?? '';

    $fachadaLogica = new FachadaLogica();
    $jugadorLogica = $fachadaLogica->retornoIJugadorLogica();
    $resultado = $jugadorLogica->registrarJugador($viejoNombre, $viejoCorreo, $password, $confirmarPassword);

    $mensajeResultado = $resultado['mensaje'];
    $exitoRegistro = $resultado['exito'];

    if ($exitoRegistro) {
        $viejoNombre = '';
        $viejoCorreo = '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - Registro</title>
    <link rel="stylesheet" href="css/estilo.css" />
</head>
<body>
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
                    <a href="registro.php" class="boton-pestania activo">Crear cuenta</a>
                </div>
            </div>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario">
                    <h2 class="titulo-formulario">REGISTRARSE</h2>

                    <?php if ($mensajeResultado !== ''): ?>
                        <div class="alerta <?= $exitoRegistro ? 'alerta-exito' : 'alerta-error' ?>" id="alerta-registro-resultado">
                            <span><?= htmlspecialchars($mensajeResultado, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="registro-form" class="formulario" action="registro.php" method="POST">
                        <div class="grupo-entrada">
                            <label for="usuario-registro">Usuario</label>
                            <input type="text" id="usuario-registro" name="usuario"
                                placeholder="Elegí un nombre de usuario ~😉"
                                value="<?= htmlspecialchars($viejoNombre, ENT_QUOTES, 'UTF-8'); ?>" required minlength="3" maxlength="67"
                                autocomplete="username" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="email-registro">Correo electrónico</label>
                            <input type="email" id="email-registro" name="email" placeholder="nombre@correo.com"
                                value="<?= htmlspecialchars($viejoCorreo, ENT_QUOTES, 'UTF-8'); ?>" required maxlength="100"
                                autocomplete="email" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="pass-registro">Contraseña</label>
                            <input type="password" id="pass-registro" name="password"
                                placeholder="Ingresá una contraseña" required minlength="8"
                                autocomplete="new-password" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="confirmar-pass">Confirmar contraseña</label>
                            <input type="password" id="confirmar-pass" name="confirm_password"
                                placeholder="Repetí la contraseña" required minlength="8"
                                autocomplete="new-password" />
                        </div>

                        <button type="submit" class="boton-enviar" id="btn-registro-submit">
                            Crear cuenta
                        </button>

                        <p class="texto-cambiar">
                            ¿Ya tenés cuenta?
                            <a href="login.php" class="boton-enlace">Iniciá sesión</a>
                        </p>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
