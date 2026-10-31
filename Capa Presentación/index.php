<?php
require_once(__DIR__ . '/Seguridad/arranque.php');

if (!str_contains($_SERVER['REQUEST_URI'] ?? '', '/DraftDerMauer')) {
    header('Location: DraftDerMauer/');
    exit();
}

if (SesionSegura::estaAutenticado()) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: panel-admin');
        exit();
    } else {
        header('Location: inicio');
        exit();
    }
}

header('Location: login');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=login">
    <title>Draft Der Mauer</title>
    <link rel="icon" type="image/png" href="./img/imgPestaña/LogoBabyDraftDerMauer.png">
</head>

<body>
    <p>Redirigiendo a <a href="login">login</a>...</p>
</body>

</html>
<?php
exit();
?>