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
$identificador = '';
$resultado = [
    'exito' => false,
    'mensaje' => '',
    'rol' => '',
    'usuario' => null,
    'redirect' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    $fachadaLogica = new FachadaLogica();
    $usuarioLogica = $fachadaLogica->retornoIUsuarioLogica();
    $resultado = $usuarioLogica->iniciarSesion($identificador, $password);

    if (!$resultado['exito']) {
        $mensajeResultado = $resultado['mensaje'];
    } else {
        $usuario = $resultado['usuario'];
        $rol = $resultado['rol'];

        $_SESSION['usuario_id'] = $usuario->getIdUsuario();
        $_SESSION['nombre_usuario'] = $usuario->getNombreUsuario();
        $_SESSION['correo'] = $usuario->getCorreo();
        $_SESSION['rol'] = $rol;

        if ($rol === 'jugador') {
            $_SESSION['fichas_actuales'] = $usuario->getFichasActuales();
            $_SESSION['cantidad_fichas'] = $usuario->getCantidadFichas();
            $_SESSION['pnts_partida'] = $usuario->getPntsPartida();
            $_SESSION['partidas_jugadas'] = $usuario->getPartidasJugadas();
            $_SESSION['partidas_ganadas'] = $usuario->getPartidasGanadas();
        }

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
                    <a href="login.php" class="boton-pestania activo">Ingresar</a>
                    <a href="registro.php" class="boton-pestania">Crear cuenta</a>
                </div>
            </div>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario">
                    <h2 class="titulo-formulario">INICIAR SESIÓN</h2>

                    <?php if ($mensajeResultado !== ''): ?>
                        <div class="alerta alerta-error" id="alerta-login-error">
                            <span><?= htmlspecialchars($mensajeResultado, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="login-form" class="formulario" action="login.php" method="POST">
                        <div class="grupo-entrada">
                            <label for="usuario-ingreso">Nombre de usuario o email</label>
                            <input type="text" id="usuario-ingreso" name="usuario" placeholder="Ingresá tu usuario"
                                value="<?= htmlspecialchars($identificador, ENT_QUOTES, 'UTF-8'); ?>" required
                                autocomplete="username" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="pass-ingreso">Contraseña</label>
                            <input type="password" id="pass-ingreso" name="password" placeholder="Ingresá tu contraseña"
                                required autocomplete="current-password" />
                        </div>

                        <button type="submit" class="boton-enviar" id="btn-login-submit">
                            Ingresar
                        </button>

                        <p class="texto-cambiar">
                            ¿No tenés cuenta todavía?
                            <a href="registro.php" class="boton-enlace">Registrate acá</a>
                        </p>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
