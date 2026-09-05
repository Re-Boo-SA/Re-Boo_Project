<?php
session_start();

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: panel_admin.php');
        exit();
    } else {
        header('Location: inicioHome.php');
        exit();
    }
}

$mensajeResultado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreUsuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $correo = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmarPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    $fachadaLogica = new FachadaLogica();
    $jugadorLogica = $fachadaLogica->retornoIJugadorLogica();

    $resultado = $jugadorLogica->registrarJugador($nombreUsuario, $correo, $password, $confirmarPassword);

    if (!$resultado['exito']) {
        $mensajeResultado = $resultado['mensaje'];
        $_SESSION['viejo_nombre'] = $nombreUsuario;
        $_SESSION['viejo_correo'] = $correo;
        $viejo_nombre = $_SESSION['viejo_nombre'];
        $viejo_correo = $_SESSION['viejo_correo'];
    } else {
        $mensajeResultado = $resultado['mensaje'];
    }
    unset($_SESSION['viejo_nombre'], $_SESSION['viejo_correo']);
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
                    <a href="registro.php" class="boton-pestania activo">Crear cuentarda</a>
                </div>
            </div>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario">
                    <h2 class="titulo-formulario">REGISTRARSE</h2>

                    <?php if (!$resultado['exito']): ?>
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

                    <?php if ($resultado['exito']): ?>
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
                        <div class="grupo-entrada">
                            <label for="usuario-registro">Usuariardo</label>
                            <input type="text" id="usuario-registro" name="usuario"
                                placeholder="Elegí un nombre de usuario ~😉"
                                value="<?= htmlspecialchars($viejo_nombre); ?>" required minlength="3" maxlength="67"
                                autocomplete="username" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="email-registro">Correo electronico</label>
                            <input type="email" id="email-registro" name="email" placeholder="nombre@correo.com"
                                value="<?= htmlspecialchars($viejo_correo); ?>" required maxlength="100"
                                autocomplete="email" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="pass-registro">Contraseñarda</label>
                            <input type="password" id="pass-registro" name="password"
                                placeholder="Ingresá una contraseñarda" required minlength="8"
                                autocomplete="new-password" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="confirmar-pass">Confirmar contraseñarda</label>
                            <input type="password" id="confirmar-pass" name="confirm_password"
                                placeholder="Repetí la contraseñarda" required minlength="8"
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
        </div>
    </div>
</body>

</html>