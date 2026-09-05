<?php
session_start();

if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: panel_admin.php');
        exit();
    } else {
        header('Location: inicioHome.php');
        exit();
    }
}

header('Location: login.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=login.php">
    <title>Draft Der Mauer</title>
</head>

<body>
    <p>Redirigiendo a <a href="login.php">login.php</a>...</p>
</body>

</html>
<?php
exit();
?>