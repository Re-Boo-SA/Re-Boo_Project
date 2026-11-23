<?php
/*
 * Tests del cerebro de los NPCs (Berlín vivo, etapa A): rutinas, ánimo, confianza,
 * memoria y diálogos. Correr con la base RECIÉN IMPORTADA.
 *     php tests/npc_test.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../Capa_Logica/FachadaLogica.php';
require_once __DIR__ . '/../Capa_Logica/NpcLogica.php';

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
$npc = new NpcLogica();
SucesoLogica::$sucesoFijo = ['tipo' => null];   // sin suceso: el ánimo y los diálogos no dependen de la hora del test
$mis = new MisionLogica();
$mundo = new MundoLogica();
const AURA = 1;
$TESTEO = (int) $db->query("SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario = 'testeo'")->fetchColumn();
$contactos = array_column(MisionLogica::datos()['contactos'], 'id');
if ((int) $db->query('SELECT COUNT(*) FROM RELACION_NPC')->fetchColumn() > 0) {
    fwrite(STDERR, "Ya hay relaciones guardadas: importá de nuevo la base y volvé a correr este test.\n");
    exit(2);
}

echo "\nRutinas\n";
$rutasLibres = true;
$cubre24 = true;
$presencia = [];
foreach ($contactos as $c) {
    $total = 0;
    foreach (NpcLogica::rutina($c) as $t) {
        $total += $t['dur'];
        if ($t['tipo'] === 'caminar') {
            for ($i = 1; $i < count($t['ruta']); $i++) {
                [$p, $q] = [$t['ruta'][$i - 1], $t['ruta'][$i]];
                $pasos = max(1, (int) ceil(hypot($q[0] - $p[0], $q[1] - $p[1]) / 8));
                for ($k = 0; $k <= $pasos; $k++) {
                    $rutasLibres = $rutasLibres && MundoLogica::libre((int) round($p[0] + ($q[0] - $p[0]) * $k / $pasos), (int) round($p[1] + ($q[1] - $p[1]) * $k / $pasos));
                }
            }
        }
    }
    $cubre24 = $cubre24 && abs($total - 24) < 0.0001;
    $horas = 0;
    for ($h = 0; $h < 24; $h += 0.25) {
        $horas += NpcLogica::posicion($c, $h)['estado'] !== 'ausente' ? 0.25 : 0;
    }
    $presencia[$c] = $horas;
}
comprobar('Ninguna ruta atraviesa paredes, edificios ni el Muro (revisado cada 8 px)', $rutasLibres, true);
comprobar('Cada rutina cubre las 24 horas del día', $cubre24, true);
comprobar('Cada contacto está en la calle al menos 15 horas por día', array_filter($presencia, fn($h) => $h < 15), []);
comprobar('A las 3 Ingrid está en su casa', NpcLogica::posicion('periodista', 3)['estado'], 'ausente');
comprobar('A las 6:03 va caminando al trabajo', NpcLogica::posicion('periodista', 6.05)['estado'], 'caminando');
$p = NpcLogica::posicion('periodista', 9);
comprobar('A las 9 escribe junto al Museo del Muro', [$p['estado'], $p['x'], $p['y']], ['quieto', 930, 1150]);
comprobar('La misma hora da siempre el mismo lugar', NpcLogica::posicion('obrero', 14.03), NpcLogica::posicion('obrero', 14.03));

echo "\nÁnimo\n";
comprobar('A Paul la lluvia lo pone de mal humor', NpcLogica::animo('obrero', 'lluvia', 12)['etiqueta'], 'malhumorado');
comprobar('Con sol está contento', NpcLogica::animo('obrero', 'despejado', 12)['etiqueta'], 'contento');
comprobar('A Lotte la niebla de noche la anima', NpcLogica::animo('espia', 'niebla', 22)['etiqueta'], 'animado');
comprobar('El mal humor se ve en la cara (emoción)', NpcLogica::animo('obrero', 'lluvia', 12)['emocion'], 'enojado');

echo "\nConfianza según el bando\n";
comprobar('Sin bando: 0 con todos', $npc->confianza(AURA, 'periodista'), 0);
$mundo->elegirBando(AURA, 'oeste');
$mundo->elegirBando($TESTEO, 'este');
comprobar('AURA (Oeste): +10 con Ingrid, -10 con Paul', [$npc->confianza(AURA, 'periodista'), $npc->confianza(AURA, 'obrero')], [10, -10]);
comprobar('testeo (Este): al revés', [$npc->confianza($TESTEO, 'periodista'), $npc->confianza($TESTEO, 'obrero')], [-10, 10]);

echo "\nDiálogos\n";
MundoLogica::$horaFija = 9.0;
MundoLogica::$climaFijo = 'despejado';
$d = $npc->empezar(AURA, 'periodista');
comprobar('La charla con Ingrid empieza por el saludo', $d['nodo'], 'inicio');
comprobar('Charlar el primer día suma 1 de confianza', $d['confianza'], 11);
comprobar('Con poca confianza no aparece la pregunta personal', in_array('¿Por qué te importa tanto el Muro?', array_column($d['opciones'], 'texto'), true), false);
comprobar('Elegirla igual (trampa) no funciona', $npc->responder(AURA, 'periodista', 'inicio', 2)['exito'], false);
$r = $npc->responder(AURA, 'periodista', 'inicio', 1);
comprobar('"Solo pasaba a saludar" suma 2', [$r['dialogo']['nodo'], $r['dialogo']['confianza']], ['saludo', 13]);
$npc->responder(AURA, 'periodista', 'inicio', 1);
$npc->empezar(AURA, 'periodista');
comprobar('Repetir la misma respuesta (o charlar de nuevo hoy) no suma más', $npc->confianza(AURA, 'periodista'), 13);
$npc->cambiarConfianza(AURA, 'periodista', 12);
$d = $npc->empezar(AURA, 'periodista');
comprobar('Con 25 de confianza aparece la pregunta personal', in_array('¿Por qué te importa tanto el Muro?', array_column($d['opciones'], 'texto'), true), true);
$r = $npc->responder(AURA, 'periodista', 'inicio', 2);
comprobar('Te cuenta lo de la nota, triste, y lo recuerda', [$r['dialogo']['nodo'], $r['dialogo']['emocion'], in_array('ingrid_te_conto_la_nota', $npc->recuerdos(AURA, 'periodista'), true)], ['nota', 'triste', true]);
comprobar('La próxima vez lo menciona', $npc->empezar(AURA, 'periodista')['nodo'], 'reencuentro');
comprobar('Paul desconfía de alguien del Oeste', $npc->empezar(AURA, 'obrero')['nodo'], 'desconfiado');
$npc->responder(AURA, 'obrero', 'desconfiado', 0);
MundoLogica::$climaFijo = 'lluvia';
$d = $npc->empezar(AURA, 'obrero');
comprobar('Después de conocerte, saluda normal... y con lluvia, de mal humor', [$d['nodo'], $d['texto']], ['inicio', 'Con este tiempo no se puede trabajar. ¿Qué querés?']);
$npc->cambiarConfianza($TESTEO, 'espia', -40);
comprobar('Con la confianza por el piso, no te habla', $npc->empezar($TESTEO, 'espia')['nodo'], 'fria');

echo "\nPresencia y misiones\n";
$mundo->mover(AURA, 0, 0, 2);
$irA = function (int $u, string $c) use ($db) {
    $p = NpcLogica::posicion($c, MundoLogica::$horaFija);
    $db->exec('UPDATE POSICIONES_MUNDO SET X = ' . ($p['x'] + 20) . ', Y = ' . $p['y'] . ' WHERE IDUsuario = ' . $u);
};
MundoLogica::$horaFija = 3.0;
$r = $mis->hablar(AURA, 'periodista');
comprobar('A las 3 Ingrid no está, y avisa cuándo vuelve', [$r['exito'], str_contains($r['mensaje'] ?? '', 'Vuelve a las 06:00')], [false, true]);
MundoLogica::$horaFija = 9.0;
comprobar('Charlar de lejos no se puede', $mis->charlar(AURA, 'periodista', '', 0)['exito'], false);
$irA(AURA, 'periodista');
comprobar('De cerca, sí (y donde está ahora, no en un lugar fijo)', $mis->charlar(AURA, 'periodista', '', 0)['exito'], true);
$antes = $npc->confianza(AURA, 'periodista');
$mis->aceptar(AURA, 'periodista_1');
$db->exec('UPDATE POSICIONES_MUNDO SET X = 1100, Y = 940 WHERE IDUsuario = ' . AURA);
$mis->estado(AURA);
$irA(AURA, 'periodista');
$mis->hablar(AURA, 'periodista');
comprobar('Completar una misión suma 15 de confianza con quien la dio', $npc->confianza(AURA, 'periodista') - $antes, 15);
$db->exec('UPDATE JUGADORES SET XP = 150 WHERE IDUsuario = ' . $TESTEO);
$mundo->mover($TESTEO, 0, 0, 2);
MundoLogica::$horaFija = 12.0;
$irA($TESTEO, 'espia');
$npc->cambiarConfianza($TESTEO, 'espia', 45);   // de -50 a -5: le habla, pero no alcanza
$o = $mis->hablar($TESTEO, 'espia');
comprobar('Lotte no le da su misión a quien no le tiene confianza (pide 10)', $o['oferta']['id'] ?? null, null);
$npc->cambiarConfianza($TESTEO, 'espia', 20);
comprobar('Con confianza suficiente, sí', $mis->hablar($TESTEO, 'espia')['oferta']['id'] ?? null, 'espia_1');

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
