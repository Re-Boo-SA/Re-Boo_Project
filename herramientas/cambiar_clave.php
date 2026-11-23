<?php
/*
 * Cambiar la contraseña de una cuenta (se corre por consola, nunca desde el navegador):
 *
 *     php herramientas/cambiar_clave.php NOMBRE_DE_USUARIO "NuevaContraseñaLarga"
 *
 * Sirve sobre todo antes de abrir el juego a internet: las cuentas de prueba del SQL
 * (AURA, pepe, testeo, juan_admin, admin_prueba) comparten una clave que está escrita en el
 * propio SQL, así que hay que cambiarlas o desactivarlas. La contraseña se hashea con
 * Argon2id y la pimienta de TU config.php, igual que en el registro.
 *
 * Con la opción --desactivar no cambia la clave: da de baja la cuenta (no podrá iniciar sesión).
 *     php herramientas/cambiar_clave.php NOMBRE --desactivar
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
$raiz = dirname(__DIR__);
if (!is_file($raiz . '/config.php')) {
    fwrite(STDERR, "✘ No existe config.php. Corré primero: php herramientas/instalar.php\n");
    exit(1);
}
if ($argc !== 3) {
    fwrite(STDERR, "Uso: php herramientas/cambiar_clave.php NOMBRE \"NuevaContraseña\"\n     php herramientas/cambiar_clave.php NOMBRE --desactivar\n");
    exit(1);
}
[$nombre, $segundo] = [$argv[1], $argv[2]];
require_once $raiz . '/Capa_Logica/SeguridadLogica.php';
require_once $raiz . '/Conexion/ConexionBD.php';
try {
    $conexion = (new ConexionBD())->connect();
} catch (Throwable $e) {
    fwrite(STDERR, "✘ No me pude conectar a la base: " . $e->getMessage() . "\n");
    exit(1);
}
$existe = $conexion->prepare('SELECT COUNT(*) FROM USUARIOS WHERE NombreUsuario = :n');
$existe->execute([':n' => $nombre]);
if ((int) $existe->fetchColumn() !== 1) {
    fwrite(STDERR, "✘ No existe el usuario «{$nombre}» (se distingue mayúsculas de minúsculas).\n");
    exit(1);
}
if ($segundo === '--desactivar') {
    $conexion->prepare('UPDATE USUARIOS SET BajaLogica = 1 WHERE NombreUsuario = :n')->execute([':n' => $nombre]);
    echo "✔ $nombre desactivado: ya no puede iniciar sesión.\n";
    exit(0);
}
$clave = $segundo;
if ($clave === 'Prueba2026!' || $clave === 'CambiarEstaClave_2026!') {
    fwrite(STDERR, "✘ Esa es una clave que está escrita en el proyecto: elegí otra.\n");
    exit(1);
}
if (mb_strlen($clave) < 12) {
    fwrite(STDERR, "✘ La contraseña tiene que tener al menos 12 caracteres.\n");
    exit(1);
}
$conexion->prepare('UPDATE USUARIOS SET Contra = :h, BajaLogica = 0 WHERE NombreUsuario = :n')
    ->execute([':h' => SeguridadLogica::hashPassword($clave), ':n' => $nombre]);
echo "✔ Contraseña de $nombre cambiada.\n";
