<?php
/*
 * Crea N jugadores de prueba (carga001, carga002...) con la contraseña Prueba2026!
 * para medir cómo aguanta el mundo abierto con mucha gente a la vez.
 *     php tests/carga/preparar.php 60
 * Solo para una base de desarrollo.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(); }
require_once __DIR__ . '/../../Capa_Logica/SeguridadLogica.php';
require_once __DIR__ . '/../../Conexion/ConexionBD.php';
$n = max(1, (int) ($argv[1] ?? 60));
$db = ConexionBD::compartida();
$hash = SeguridadLogica::hashPassword('Prueba2026!');
$db->exec("DELETE FROM POSICIONES_MUNDO WHERE IDUsuario IN (SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario LIKE 'carga%')");
$db->exec("DELETE FROM MENSAJES_MUNDO WHERE IDUsuario IN (SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario LIKE 'carga%')");
$db->exec("DELETE FROM JUGADORES WHERE IDUsuario IN (SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario LIKE 'carga%')");
$db->exec("DELETE FROM USUARIOS WHERE NombreUsuario LIKE 'carga%'");
$u = $db->prepare("INSERT INTO USUARIOS (Correo, Contra, NombreUsuario, Rol) VALUES (:c, :h, :n, 'jugador')");
$j = $db->prepare("INSERT INTO JUGADORES (IDUsuario) VALUES (:id)");
for ($i = 1; $i <= $n; $i++) {
    $nombre = sprintf('carga%03d', $i);
    $u->execute([':c' => "$nombre@carga.local", ':h' => $hash, ':n' => $nombre]);
    $j->execute([':id' => $db->lastInsertId()]);
}
echo "Creados $n jugadores de carga.\n";
