<?php
/* Personalización: el personaje del mundo abierto (código validado y que viaja con la posición) y el perfil al estilo Discord.
   Correr con la base recién importada:  php tests/personalizacion_test.php */
require_once __DIR__ . '/../Capa_Logica/FachadaLogica.php';
$aprobados = 0; $fallados = 0;
function comprobar(string $nombre, $obtenido, $esperado): void {
    global $aprobados, $fallados;
    if ($obtenido === $esperado) { $aprobados++; echo "  ✔ $nombre\n"; }
    else { $fallados++; echo "  ✘ $nombre\n      esperado: " . json_encode($esperado, JSON_UNESCAPED_UNICODE) . "\n      obtenido: " . json_encode($obtenido, JSON_UNESCAPED_UNICODE) . "\n"; }
}
$db = ConexionBD::compartida();
if ((int) $db->query('SELECT COUNT(*) FROM PERFIL_PERSONAL')->fetchColumn() > 0) { echo "Ya hay perfiles personalizados: importá de nuevo la base.\n"; exit(1); }
const AURA = 1;
$TESTEO = (int) $db->query("SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario = 'testeo'")->fetchColumn();
$mundo = new MundoLogica(); $social = new SocialLogica();

echo "Personaje\n";
$bueno = 'p1s2a1x9-f1c9a52b1d147a1e28501419' . '2b2f3a4a3b2eb42828';
foreach (['' => false, 'hola' => false, 'p5s0a0x0-' . str_repeat('a', 42) => false, 'p0s0a3x0-' . str_repeat('a', 42) => false, 'p0s0a0x0-' . str_repeat('g', 42) => false,
          'p0s0a0x0-' . str_repeat('a', 41) => false, 'p0s0a0x0-' . str_repeat('a', 42) . '<' => false, $bueno => true, strtoupper($bueno) => true] as $codigo => $ok) {
    comprobar('Código ' . ($codigo === '' ? '(vacío)' : substr($codigo, 0, 14) . '…') . ($ok ? ' se acepta' : ' se rechaza'), $mundo->guardarPersonaje(AURA, $codigo)['exito'], $ok);
}
comprobar('Se guarda en minúsculas', (new FachadaPersistencia())->retornoIPersistenciaMundo()->personaje(AURA), $bueno);
// Los dos entran a Berlín al lado de la puerta del café 4 (lugar caminable, como en mundo_test)
$cafe4 = array_values(array_filter(MundoLogica::mapaTiled()['edificios'], fn($e) => $e['tipo'] === 'cafe' && $e['distrito'] === 4))[0];
[$x, $y] = [$cafe4['puertaX'] + 20, $cafe4['puertaY'] + 40];
$r = $mundo->mover(AURA, $x, $y, 2);
comprobar('Entro a Berlín', isset($r['x'], $r['y']), true);
$e = $mundo->estado(AURA);
comprobar('El estado del mundo trae mi personaje', $e['yo']['personal'] ?? null, $bueno);
$mundo->mover($TESTEO, $x + 10, $y, 2);
$vistos = array_column($mundo->estadoLiviano($TESTEO)['jugadores'] ?? [], 'per', 'id');
comprobar('Los demás me ven con mi personaje', $vistos[AURA] ?? null, $bueno);
$otro = 'p4s0a2x8-ffe0c4aa281e1f2a40172033141414b3262be9e4cc';
$mundo->guardarPersonaje(AURA, $otro);
$vistos = array_column($mundo->estadoLiviano($TESTEO)['jugadores'] ?? [], 'per', 'id');
comprobar('Si lo cambio, se actualiza enseguida para los demás', $vistos[AURA] ?? null, $otro);

echo "\nPerfil al estilo Discord\n";
$base = ['banner1' => '#1b2440', 'banner2' => '#3a2a4a', 'acento' => '#f4c430', 'estadoEmoji' => '🎲', 'estadoTexto' => 'Jugando en Kreuzberg', 'pronombres' => 'ella/la', 'insignias' => [], 'marco' => 'ninguno'];
comprobar('Un perfil válido se guarda', $social->guardarPersonalizacion(AURA, $base)['exito'], true);
foreach ([['acento', '#zzz000'], ['banner1', 'red'], ['banner2', '#12345']] as [$k, $v]) comprobar("Color inválido en $k: se rechaza", $social->guardarPersonalizacion(AURA, [$k => $v] + $base)['exito'], false);
foreach (['hola', '<b>', '🎲🎲🎲🎲🎲🎲🎲🎲🎲', 'a🎲'] as $v) comprobar("Estado con «{$v}»: se rechaza", $social->guardarPersonalizacion(AURA, ['estadoEmoji' => $v] + $base)['exito'], false);
foreach (['♟️', '🕵️', '👩‍💻', ''] as $v) comprobar("Estado con «" . ($v ?: 'nada') . "»: se acepta", $social->guardarPersonalizacion(AURA, ['estadoEmoji' => $v] + $base)['exito'], true);
comprobar('Pronombres con números: se rechazan', $social->guardarPersonalizacion(AURA, ['pronombres' => 'él123'] + $base)['exito'], false);
$social->guardarPersonalizacion(AURA, ['estadoTexto' => str_repeat('x', 120) . "<script>\x07"] + $base);
$p = $social->perfil($TESTEO, AURA)['perfil'];
comprobar('El texto del estado se recorta a 80 y sin < > ni controles', [mb_strlen($p['tarjeta']['estadoTexto']), str_contains($p['tarjeta']['estadoTexto'], '<')], [80, false]);
comprobar('Destacar un logro que no ganaste: se rechaza', $social->guardarPersonalizacion(AURA, ['insignias' => ['campeon_torneo']] + $base)['exito'], false);
$db->exec("INSERT INTO LOGROS_JUGADOR (IDUsuario, IDLogro) VALUES (1, 'nacht_prologo'), (1, 'campeon_torneo')");
comprobar('Uno que sí ganaste: se acepta', $social->guardarPersonalizacion(AURA, ['insignias' => ['nacht_prologo']] + $base)['exito'], true);
comprobar('Más de 3: se rechaza', $social->guardarPersonalizacion(AURA, ['insignias' => ['a', 'b', 'c', 'd']] + $base)['exito'], false);
comprobar('Marco bloqueado (oro, sin torneos): se rechaza', $social->guardarPersonalizacion(AURA, ['marco' => 'oro'] + $base)['exito'], false);
comprobar('Marco desbloqueado (muro, por el prólogo): se acepta', $social->guardarPersonalizacion(AURA, ['marco' => 'muro', 'insignias' => ['nacht_prologo']] + $base)['exito'], true);
comprobar('Marco que no existe: se rechaza', $social->guardarPersonalizacion(AURA, ['marco' => 'diamante'] + $base)['exito'], false);
$p = $social->perfil($TESTEO, AURA)['perfil'];
comprobar('Otro jugador ve mi tarjeta: banner, acento, estado, pronombres, marco, logro destacado y mi personaje',
    [$p['tarjeta']['banner1'], $p['tarjeta']['acento'], $p['tarjeta']['estadoEmoji'], $p['tarjeta']['pronombres'], $p['tarjeta']['marco'], array_column($p['tarjeta']['insignias'], 'id'), $p['personal']],
    ['#1b2440', '#f4c430', '🎲', 'ella/la', 'muro', ['nacht_prologo'], $otro]);
$opc = $social->personalizacion(AURA);
comprobar('La página de personalizar lista los marcos con su condición', [count($opc['marcos']), array_column($opc['marcos'], 'desbloqueado', 'id')['muro'], array_column($opc['marcos'], 'desbloqueado', 'id')['oro']], [8, true, false]);
// Un marco o un logro guardados que el jugador ya no tiene (o que nunca tuvo) no se muestran
$db->exec("UPDATE PERFIL_PERSONAL SET Marco = 'oro', Insignias = 'nacht_capitulo_6' WHERE IDUsuario = 1");
$p = $social->perfil($TESTEO, AURA)['perfil'];
comprobar('Un marco o un logro que no tiene, no se muestran', [$p['tarjeta']['marco'], $p['tarjeta']['insignias']], ['ninguno', []]);

echo "\n" . ($fallados === 0 ? "TODO BIEN: $aprobados aprobados, 0 fallados\n" : "HAY FALLAS: $aprobados aprobados, $fallados fallados\n");
exit($fallados === 0 ? 0 : 1);
