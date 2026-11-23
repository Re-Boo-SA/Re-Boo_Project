<?php
/*
 * Tests de sucesos, diario y reacciones de los contactos (Berlín vivo, etapa B).
 * Correr con la base RECIÉN IMPORTADA.
 *     php tests/sucesos_test.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../Capa_Logica/FachadaLogica.php';
require_once __DIR__ . '/../Capa_Logica/NpcLogica.php';
require_once __DIR__ . '/../Capa_Logica/DiarioLogica.php';

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
$mundo = new MundoLogica();
$pl = new PartidaLogica();
const AURA = 1;
$TESTEO = (int) $db->query("SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario = 'testeo'")->fetchColumn();
$datos = SucesoLogica::datos();

echo "\nSorteo\n";
$cuenta = [];
$t0 = SucesoLogica::tramo();
for ($i = 0; $i < 4000; $i++) {
    $k = SucesoLogica::tipoDeTramo($t0 + $i) ?? 'ninguno';
    $cuenta[$k] = ($cuenta[$k] ?? 0) + 1;
}
comprobar('Hay suceso en ~70% de los tramos (±4 puntos)', abs((4000 - $cuenta['ninguno']) / 40 - $datos['probabilidad']) < 4, true);
$total = array_sum(array_column($datos['tipos'], 'peso'));
$parecido = true;
foreach ($datos['tipos'] as $tipo) {
    $esperado = ($tipo['peso'] / $total) * (4000 - $cuenta['ninguno']);
    $parecido = $parecido && abs(($cuenta[$tipo['id']] ?? 0) - $esperado) < $esperado * 0.25 + 15;
}
comprobar('Cada tipo sale según su peso', $parecido, true);
comprobar('El mismo tramo da siempre el mismo suceso', SucesoLogica::tipoDeTramo($t0 + 7), SucesoLogica::tipoDeTramo($t0 + 7));
$soloOeste = true;
$soloTiergarten = true;
$lados = array_column((new FachadaPersistencia())->retornoIPersistenciaMundo()->distritos(), 'Lado', 'IDDistrito');
for ($i = 0; $i < 600; $i++) {
    $s = SucesoLogica::deTramo($t0 + $i);
    if ($s && $s['tipo'] === 'manifestacion') $soloOeste = $soloOeste && $lados[$s['distrito']] === 'oeste';
    if ($s && $s['tipo'] === 'concierto') $soloTiergarten = $soloTiergarten && $s['distrito'] === 4;
}
comprobar('Las manifestaciones solo ocurren en el Oeste', $soloOeste, true);
comprobar('El concierto es en Tiergarten', $soloTiergarten, true);

echo "\nControl sorpresa en Checkpoint Charlie\n";
SucesoLogica::$sucesoFijo = ['tipo' => 'control_checkpoint'];
comprobar('Un paso largo dentro del paso se rechaza', MundoLogica::movimientoValido(1140, 940, 1260, 940, 0.3), false);
comprobar('Uno corto, sí', MundoLogica::movimientoValido(1140, 940, 1200, 940, 0.3), true);
comprobar('Fuera del paso se camina normal', MundoLogica::movimientoValido(980, 700, 980, 820, 0.3), true);
SucesoLogica::$sucesoFijo = null;
if (SucesoLogica::tipoDeTramo(SucesoLogica::tramo()) !== 'control_checkpoint') {
    comprobar('Sin control, el paso largo vale', MundoLogica::movimientoValido(1140, 940, 1260, 940, 0.3), true);
}

echo "\nNoche de torneo en un café\n";
$mundo->elegirBando(AURA, 'oeste');
$mundo->elegirBando($TESTEO, 'oeste');
SucesoLogica::$sucesoFijo = ['tipo' => 'torneo_cafe', 'distrito' => 4];
comprobar('Las partidas del café de Tiergarten valen doble', [SucesoLogica::multiplicadorInfluencia(4), SucesoLogica::multiplicadorInfluencia(9), SucesoLogica::multiplicadorInfluencia(null)], [2, 1, 1]);
$temporada = (int) $mundo->estado(AURA)['temporada']['numero'];
$antes = (int) ($mundo->estado(AURA)['dominio'][4]['oeste'] ?? 0);
$id = $pl->crearPartida(AURA, 'Mesa en Tiergarten', 'publica', '', 2, 'normal')['idPartida'];
(new FachadaPersistencia())->retornoIPersistenciaMundo()->asignarDistrito($id, 4);
$pl->unirse($TESTEO, $id, '');
$pl->accion($id, $TESTEO, 'salir', []);
$despues = (int) ($mundo->estado(AURA)['dominio'][4]['oeste'] ?? 0);
comprobar('Una partida real ganada en ese café suma 3 x 2 = 6 de influencia', $despues - $antes, 6);

echo "\nMercado callejero\n";
SucesoLogica::$sucesoFijo = ['tipo' => 'mercado', 'distrito' => 1];
$s = SucesoLogica::actual();
$sl = new SucesoLogica();
$mundo->mover(AURA, 0, 0, 2);
comprobar('Lejos de los puestos no cuenta', $sl->visitar(AURA)['exito'], false);
$db->exec('UPDATE POSICIONES_MUNDO SET X = ' . $s['x'] . ', Y = ' . ($s['y'] + 10) . ' WHERE IDUsuario = ' . AURA);
$xp = (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn();
comprobar('Cerca, da 25 XP', [$sl->visitar(AURA)['exito'], (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn() - $xp], [true, 25]);
comprobar('Una sola vez por suceso', $sl->visitar(AURA)['exito'], false);

echo "\nLos contactos reaccionan\n";
MundoLogica::$horaFija = 12.0;
MundoLogica::$climaFijo = 'despejado';
$npc = new NpcLogica();
$normal = NpcLogica::animo('periodista')['valor'];
SucesoLogica::$sucesoFijo = ['tipo' => 'manifestacion', 'distrito' => 6];
comprobar('Con una manifestación Ingrid se anima (+1)', NpcLogica::animo('periodista')['valor'] - $normal, 1);
comprobar('Y te habla de eso', $npc->empezar(AURA, 'periodista')['nodo'], 'suceso_manifestacion');
SucesoLogica::$sucesoFijo = ['tipo' => 'control_checkpoint'];
$d = $npc->empezar($TESTEO, 'obrero');
comprobar('Con el control en el paso, Paul está triste por lo de su hermano', [$d['nodo'], $d['emocion']], ['suceso_control_checkpoint', 'triste']);
$npc->cambiarConfianza($TESTEO, 'obrero', -60);
comprobar('Si no te tiene confianza, ni eso te cuenta', $npc->empezar($TESTEO, 'obrero')['nodo'], 'fria');

echo "\nDiario del día\n";
SucesoLogica::$sucesoFijo = ['tipo' => 'apagon', 'distrito' => 9];
$o = (new DiarioLogica())->edicion('oeste');
$e = (new DiarioLogica())->edicion('este');
comprobar('Un diario de cada lado', [$o['nombre'], $e['nombre']], ['Der Berliner Abendbote', 'Stimme der Hauptstadt']);
comprobar('Cuenta el suceso del día', count(array_filter($o['titulares'], fn($t) => $t['prefijo'] === 'Hoy' && $t['plantilla'] === 'Apagón en {distrito}')), 1);
comprobar('Con el distrito como valor (para traducir la plantilla)', array_values(array_filter($o['titulares'], fn($t) => $t['prefijo'] === 'Hoy'))[0]['valores']->distrito, 'Mitte');
comprobar('Los mismos hechos con distinto tono', $o['titulares'][0]['plantilla'] !== $e['titulares'][0]['plantilla'] || str_starts_with($o['titulares'][0]['plantilla'], 'Empate'), true);
comprobar('El presente del juego es 1975', $o['fecha']['anio'], 1975);

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
