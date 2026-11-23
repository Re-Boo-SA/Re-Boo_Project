<?php
/*
 * Instalador de Re-Boo (se corre por consola, nunca desde el navegador):
 *
 *     php herramientas/instalar.php
 *
 * 1. Si no existe config.php, lo crea a partir de config.example.php con
 *    una pimienta aleatoria nueva.
 * 2. Le pone la contraseña "Prueba2026!" a las cuentas de prueba del SQL
 *    (hasheada con la pimienta de TU config.php).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

$raiz = dirname(__DIR__);
$config = $raiz . '/config.php';
$ejemplo = $raiz . '/config.example.php';

if (!is_file($config)) {
    $texto = file_get_contents($ejemplo);
    if ($texto === false) {
        fwrite(STDERR, "No se encontró config.example.php\n");
        exit(1);
    }
    $pimienta = bin2hex(random_bytes(32));
    $texto = str_replace('CAMBIAR_POR_UNA_CADENA_LARGA_Y_ALEATORIA', $pimienta, $texto);
    if (@file_put_contents($config, $texto) === false) {
        fwrite(STDERR, "✘ No pude escribir config.php en: $raiz\n");
        fwrite(STDERR, "  Copiá el proyecto a una ruta sin acentos (por ejemplo C:\\Re-Boo) y volvé a probar.\n");
        exit(1);
    }
    echo "✔ config.php creado con una pimienta nueva.\n";
    echo "  Revisá BD_USUARIO y BD_CONTRA si tu MySQL usa otros datos.\n";
} else {
    echo "• config.php ya existe, no se toca.\n";
}

require_once $raiz . '/Capa_Logica/SeguridadLogica.php';
require_once $raiz . '/Conexion/ConexionBD.php';

$cuentas = ['AURA', 'pepe', 'testeo', 'juan_admin', 'admin_prueba'];
$contra = 'Prueba2026!';

try {
    $conexion = (new ConexionBD())->connect();
} catch (Throwable $e) {
    fwrite(STDERR, "✘ No me pude conectar a la base: " . $e->getMessage() . "\n");
    fwrite(STDERR, "  ¿Importaste sql/Proyecto 2026, BDDv3.sql y están bien los datos en config.php?\n");
    exit(1);
}

$sentencia = $conexion->prepare('UPDATE USUARIOS SET Contra = :hash WHERE NombreUsuario = :nombre');
foreach ($cuentas as $nombre) {
    $sentencia->execute([':hash' => SeguridadLogica::hashPassword($contra), ':nombre' => $nombre]);
    echo ($sentencia->rowCount() === 1 ? '✔' : '•') . " $nombre\n";
}
echo "\nListo. Todas las cuentas de prueba usan la contraseña: $contra\n";
