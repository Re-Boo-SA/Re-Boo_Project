<?php
/*
 * Tests de lo social (Fase 4): perfil, amigos, bloqueos, grupos, invitaciones,
 * mirar partidas y gestos. Usa la base real: correrlo con la base RECIÉN IMPORTADA.
 *     php tests/social_test.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../Capa_Logica/FachadaLogica.php';

$aprobados = 0;
$fallados = 0;
function comprobar(string $nombre, $obtenido, $esperado): void
{
    global $aprobados, $fallados;
    if ($obtenido === $esperado) {
        $aprobados++;
        echo "  ✔ $nombre\n";
    } else {
        $fallados++;
        echo "  ✘ $nombre\n      esperado: " . json_encode($esperado, JSON_UNESCAPED_UNICODE) . "\n      obtenido: " . json_encode($obtenido, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

$db = ConexionBD::compartida();
$s = new SocialLogica();
$mundo = new MundoLogica();
$partidas = new PartidaLogica();
$id = fn(string $n) => (int) $db->query('SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario = ' . $db->quote($n))->fetchColumn();
const AURA = 1;
$TESTEO = $id('testeo');
if ((int) $db->query('SELECT COUNT(*) FROM AMISTADES')->fetchColumn() > 0) {
    fwrite(STDERR, "Ya hay amistades: importá de nuevo la base y volvé a correr este test.\n");
    exit(2);
}
// Jugadores extra para probar grupos llenos
$hash = SeguridadLogica::hashPassword('Prueba2026!');
$extra = [];
foreach (['social1', 'social2', 'social3', 'social4'] as $n) {
    $db->prepare("INSERT INTO USUARIOS (Correo, Contra, NombreUsuario, Rol) VALUES (?, ?, ?, 'jugador')")->execute(["$n@test.local", $hash, $n]);
    $nuevo = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO JUGADORES (IDUsuario) VALUES (?)')->execute([$nuevo]);
    $extra[$n] = $nuevo;
}
[$S1, $S2, $S3, $S4] = array_values($extra);
$bot = (int) $db->query('SELECT IDUsuario FROM JUGADORES WHERE EsBot = 1 LIMIT 1')->fetchColumn();
$admin = $id('pepe');

echo "\nPerfil público\n";
$p = $s->perfil(AURA, $TESTEO);
comprobar('Se ve el perfil de testeo (nivel, rango, relación)', [$p['exito'], $p['perfil']['nombre'], $p['perfil']['relacion'], isset($p['perfil']['nivel'], $p['perfil']['rango'])], [true, 'testeo', 'ninguna', true]);
comprobar('Sin ser amigos no se ve dónde está', isset($p['perfil']['presencia']), false);
comprobar('Los administradores no tienen perfil de jugador', $s->perfil(AURA, $admin)['exito'], false);
comprobar('Buscar por nombre encuentra a testeo y no a uno mismo', array_column($s->buscar(AURA, 'tes')['jugadores'], 'nombre'), ['testeo']);

echo "\nAmigos\n";
comprobar('No te podés agregar a vos', $s->solicitar(AURA, AURA)['exito'], false);
comprobar('A un bot no se lo agrega', $s->solicitar(AURA, $bot)['exito'], false);
comprobar('AURA le pide a testeo', $s->solicitar(AURA, $TESTEO)['relacion'] ?? null, 'enviada');
comprobar('No se pide dos veces', $s->solicitar(AURA, $TESTEO)['exito'], false);
comprobar('Si testeo también le pide, quedan amigos', $s->solicitar($TESTEO, AURA)['relacion'] ?? null, 'amigos');
$p = $s->perfil(AURA, $TESTEO)['perfil'];
comprobar('Entre amigos se ve dónde está', [$p['relacion'], isset($p['presencia']['estado'])], ['amigos', true]);
$s->solicitar($S1, AURA);
comprobar('AURA ve la solicitud recibida de social1', array_column($s->panel(AURA)['recibidas'], 'nombre'), ['social1']);
comprobar('Rechazar la borra', [$s->responder(AURA, $S1, false)['exito'], $s->perfil(AURA, $S1)['perfil']['relacion']], [true, 'ninguna']);

echo "\nBloqueos\n";
$s->solicitar($S2, AURA);
comprobar('AURA bloquea a social2', $s->bloquear(AURA, $S2)['exito'], true);
comprobar('Bloquear borra la solicitud', $s->panel(AURA)['recibidas'], []);
comprobar('El bloqueado no puede volver a pedir amistad', $s->solicitar($S2, AURA)['exito'], false);
comprobar('Para el bloqueado, AURA "no existe"', $s->perfil($S2, AURA)['exito'], false);
$mundo->elegirBando(AURA, 'oeste');
$mundo->elegirBando($S2, 'oeste');
$mundo->mover(AURA, 0, 0, 2);
$mundo->mover($S2, 0, 0, 2);
$mundo->decir($S2, 'hola, soy social2');
$ve = fn(int $quien, int $a) => in_array($a, array_column($mundo->estadoLiviano($quien)['jugadores'], 'id'), true);
comprobar('En Berlín no se ven (en ningún sentido)', [$ve(AURA, $S2), $ve($S2, AURA)], [false, false]);
comprobar('Y AURA no lee sus mensajes', in_array($S2, array_column($mundo->estadoLiviano(AURA)['mensajes'], 'jugador'), true), false);
$s->desbloquear(AURA, $S2);
comprobar('Al desbloquear se vuelven a ver', $ve(AURA, $S2), true);

echo "\nGrupos\n";
foreach ([$TESTEO, $S1, $S3, $S4] as $u) {
    $s->invitarAGrupo(AURA, $u);
}
$inv = fn(int $u) => array_values(array_filter($s->panel($u)['invitaciones'], fn($i) => $i['tipo'] === 'grupo'))[0]['id'] ?? 0;
foreach ([$TESTEO, $S1, $S3] as $u) {
    $s->aceptarInvitacion($u, $inv($u));
}
comprobar('El grupo llega a 4', count($s->grupo(AURA)['grupo']['miembros']), 4);
comprobar('Un quinto no entra aunque tenga invitación', $s->aceptarInvitacion($S4, $inv($S4))['exito'], false);
comprobar('Y no se puede invitar a más', $s->invitarAGrupo(AURA, $S2)['exito'], false);
$s->decirAlGrupo($TESTEO, 'nos vemos en el café de Mitte');
comprobar('El chat del grupo llega a todos', array_column($s->grupo($S1)['mensajes'], 'texto'), ['nos vemos en el café de Mitte']);
comprobar('Quien no está en el grupo no puede escribir', $s->decirAlGrupo($S4, 'hola')['exito'], false);
comprobar('Solo el líder echa gente', $s->expulsar($TESTEO, $S1)['exito'], false);
$s->salirDelGrupo(AURA);
$g = $s->grupo($TESTEO)['grupo'];
comprobar('Si el líder se va, lidera el que hace más que está', [count($g['miembros']), $g['soyLider']], [3, true]);
$s->expulsar($TESTEO, $S1);
$s->salirDelGrupo($S3);
comprobar('Un grupo de uno se disuelve', $s->grupo($TESTEO)['grupo'], null);

echo "\nInvitar a la mesa y mirar partidas\n";
$r = $partidas->crearPartida(AURA, 'Mesa privada de AURA', 'privada', 'clave-secreta', 4, 'normal');
$mesa = $r['idPartida'];
comprobar('Solo se invita a amigos', $s->invitarAMesa(AURA, $S3)['exito'], false);
comprobar('Invita a testeo (amigo)', $s->invitarAMesa(AURA, $TESTEO)['exito'], true);
$im = array_values(array_filter($s->panel($TESTEO)['invitaciones'], fn($i) => $i['tipo'] === 'mesa'));
comprobar('testeo entra a la mesa privada sin la contraseña', $s->aceptarInvitacion($TESTEO, $im[0]['id'])['exito'] ?? false, true);
$partidas->accion($mesa, AURA, 'completar_bots', []);
comprobar('Un desconocido no puede mirar una partida privada', $s->puedeMirar($S3, $mesa)['exito'], false);
$s->solicitar($S3, AURA);
$s->responder(AURA, $S3, true);
$v = $partidas->vistaEspectador($mesa, $S3);
comprobar('Un amigo de AURA sí puede mirarla', [$v['exito'], $v['espectador'] ?? null], [true, true]);
comprobar('El espectador no recibe ninguna mano', $v['yo']['mano'], []);
comprobar('Pero ve cuántas fichas tiene cada uno', count(array_filter($v['jugadores'], fn($j) => $j['fichasEnMano'] > 0)) > 0, true);
comprobar('Mirar no deja jugar', $partidas->accion($mesa, $S3, 'tirar', [])['exito'], false);

echo "\nGestos\n";
comprobar('Un gesto que no existe se rechaza', $mundo->gesto(AURA, 9)['exito'], false);
$mundo->gesto(AURA, 1);
$gestoDe = fn(int $quien, int $de) => array_values(array_filter($mundo->estadoLiviano($quien)['jugadores'], fn($j) => $j['id'] === $de))[0]['gesto'] ?? null;
comprobar('Los demás ven a AURA saludar', $gestoDe($S2, AURA), 1);
$pos = $db->query('SELECT X, Y FROM POSICIONES_MUNDO WHERE IDUsuario = ' . AURA)->fetch(PDO::FETCH_ASSOC);
$db->exec('UPDATE POSICIONES_MUNDO SET UltimaActividad = NOW(3) - INTERVAL 1 SECOND WHERE IDUsuario = ' . AURA);
$mundo->mover(AURA, (int) $pos['X'] + 20, (int) $pos['Y'], 1);
comprobar('Al caminar, el gesto se corta', $gestoDe($S2, AURA), 0);

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
