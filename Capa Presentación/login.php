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
$identificador = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

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
            $mensajeResultado = $resultado['mensaje'];
        } elseif ($rol === 'administrador') {
            $mensajeResultado = $resultado['mensaje'];
        }

        $destino = !empty($resultado['redirect']) ? ($resultado['redirect'] === 'inicioHome.php' ? 'inicioHome.php' : $resultado['redirect']) : ($rol === 'administrador' ? 'panel_admin.php' : 'login.php');
        header("Location: $destino");
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
                    <a href="registro.php" class="boton-pestania">Crear cuentarda</a>
                </div>
            </div>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario">
                    <h2 class="titulo-formulario">INICIAR SESIÓN</h2>

                    <?php if (!$resultado['exito']): ?>
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

                    <?php if ($resultado['exito']): ?>
                        <div class="alert alert-success" id="alert-login-success">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span><?= htmlspecialchars($resultado['mensaje']) ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="login-form" class="formulario" action="login.php" method="POST">
                        <div class="grupo-entrada">
                            <label for="usuario-ingreso">Nombre de usuariardo o email</label>
                            <input type="text" id="usuario-ingreso" name="usuario" placeholder="Ingresá tu usuario"
                                value="<?php echo htmlspecialchars($identificador); ?>" required
                                autocomplete="username" />
                        </div>

                        <div class="grupo-entrada">
                            <label for="pass-ingreso">Contraseñarda</label>
                            <input type="password" id="pass-ingreso" name="password" placeholder="Ingresá tu contraseña"
                                required autocomplete="current-password" />
                        </div>

                        <button type="submit" class="boton-enviar" id="btn-login-submit">
                            Ingresar
                        </button>

                        <p class="texto-cambiar">
                            ¿No tenés cuentarda todavía?
                            <a href="registro.php" class="boton-enlace">Registrate acá</a>
                        </p>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>