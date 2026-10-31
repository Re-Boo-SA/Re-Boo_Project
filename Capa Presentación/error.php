<?php
// Página de error genérica: nunca muestra detalles técnicos, solo un mensaje para el usuario.
require_once(__DIR__ . '/Seguridad/arranque.php');

$errores = [
    400 => ['Pedido inválido', 'Los datos enviados no son válidos.'],
    401 => ['Iniciá sesión', 'Necesitás iniciar sesión para ver esta página.'],
    403 => ['Acceso denegado', 'No tenés permiso para hacer esto, o el formulario venció. Volvé a la página anterior y recargala.'],
    404 => ['67676767676767676767676767'],
    405 => ['Método no permitido', 'Esta dirección no acepta ese tipo de pedido.'],
    413 => ['Archivo demasiado grande', 'Lo que enviaste supera el tamaño máximo permitido.'],
    429 => ['Demasiados pedidos', 'Hiciste demasiadas acciones seguidas. Esperá unos minutos.'],
    500 => ['Error del servidor', 'Ocurrió un error inesperado. Ya quedó registrado.'],
    503 => ['Servicio no disponible', 'El sistema no está disponible en este momento. Probá más tarde.']
];

$codigo = (int) ($codigoError ?? ($_SERVER['REDIRECT_STATUS'] ?? 404));
if (!isset($errores[$codigo])) {
    $codigo = 404;
}
http_response_code($codigo);
[$tituloError, $mensajeError] = $errores[$codigo];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Draft Der Mauer - <?= e($tituloError) ?></title>
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
                    <p class="subtitulo">ERROR <?= $codigo ?></p>
                </div>
            </header>

            <main class="contenedor-formulario">
                <div class="tarjeta-formulario">
                    <h2 class="titulo-formulario"><?= e($tituloError) ?></h2>
                    <p class="texto-cambiar"><?= e($mensajeError) ?></p>
                    <a href="index.php" class="boton-enviar boton-enlace-bloque">Volver al inicio</a>
                </div>
            </main>
        </div>
    </div>

    <script src="./js/comun.js"></script>
    <script src="./js/traduccion.js"></script>
</body>

</html>
