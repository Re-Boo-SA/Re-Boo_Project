<?php
/*
 * Tests del combate táctico por turnos (campaña, etapa D). Correr con la base RECIÉN IMPORTADA.
 *     php tests/combate_test.php
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
const AURA = 1;
$guion = CampanaLogica::guion('prologo');
$def = null;
foreach ($guion['pasos'] as $p) if ($p['tipo'] === 'combate') $def = $p;
$mapa = json_decode(file_get_contents(__DIR__ . '/../datos/campana/mapas/estacion.json'), true);
$nuevo = fn(int $semilla = 7) => CombateLogica::nuevo($def, $mapa, $semilla);

echo "\nPreparación\n";
$e = $nuevo();
$libres = true;
foreach ($e['unidades'] as $id => $u) $libres = $libres && !$e['grilla']['solido'][$u['y']][$u['x']];
comprobar('Todos arrancan en casillas libres', $libres, true);
comprobar('Empieza tu turno, en la ronda 1', [$e['turno'], $e['ronda'], $e['resultado']], ['aliado', 1, null]);
$v = CombateLogica::vista($e);
comprobar('La vista no tiene la semilla ni la grilla', [isset($v['semilla']), isset($v['grilla']), isset($v['tiradas'])], [false, false, false]);
comprobar('La vista dice adónde puede ir y a quién puede tirarle cada personaje', [count($v['acciones']->viktor['alcanzables']) > 0, isset($v['acciones']->friedrich['tiros']->maton_1)], [true, true]);

echo "\nMovimiento\n";
$alc = CombateLogica::alcanzables($e, 'viktor');
comprobar('No llega más lejos que su movimiento (4)', max(array_map(fn($c) => abs($c[0] - 19) + abs($c[1] - 9), $alc)) <= 4, true);
comprobar('Nunca a una casilla con obstáculo', array_filter($alc, fn($c) => $e['grilla']['solido'][$c[1]][$c[0]]), []);
comprobar('Demasiado lejos: no', CombateLogica::mover($e, 'viktor', 10, 9), 'No puede llegar hasta ahí.');
comprobar('Sobre un cajón: no', CombateLogica::mover($e, 'viktor', 18, 9), 'No puede llegar hasta ahí.');
comprobar('A un rival no se lo mueve', CombateLogica::mover($e, 'maton_1', 21, 5), 'Ese personaje no puede actuar ahora.');
comprobar('Una casilla válida, sí', [CombateLogica::mover($e, 'viktor', 19, 11), $e['unidades']['viktor']['x'], $e['unidades']['viktor']['y']], [null, 19, 11]);
comprobar('Una sola vez por turno', CombateLogica::mover($e, 'viktor', 19, 10), 'Ese personaje ya se movió o actuó en este turno.');

echo "\nLínea de vista y cobertura\n";
$e = $nuevo();
comprobar('El vagón tapa la vista', CombateLogica::lineaDeVista($e, 13, 13, 13, 10), false);
comprobar('Al lado, se ve', CombateLogica::lineaDeVista($e, 19, 9, 19, 10), true);
comprobar('Friedrich, con el vagón al norte, tiene cobertura contra un tiro del norte', CombateLogica::cobertura($e, 12, 13, 12, 5), true);
comprobar('Pero no contra uno del sur', CombateLogica::cobertura($e, 12, 13, 12, 15), false);
$abierto = CombateLogica::probabilidad($e, 'maton_3', 'friedrich', [16, 15]);
$cubierto = CombateLogica::probabilidad($e, 'maton_3', 'friedrich', [12, 8]);
comprobar('La cobertura baja la probabilidad', $cubierto < $abierto || $cubierto === 0, true);
$todas = [];
foreach (['viktor', 'friedrich'] as $a) foreach (['maton_1', 'maton_2', 'maton_3'] as $b) $todas[] = CombateLogica::probabilidad($e, $a, $b);
comprobar('Las probabilidades quedan entre 0 (sin vista) y 90', min($todas) >= 0 && max($todas) <= 90, true);

echo "\nDisparos y cubrirse\n";
$e = $nuevo();
$sinVista = null;
foreach (['maton_1', 'maton_2', 'maton_3'] as $b) if (CombateLogica::probabilidad($e, 'friedrich', $b) === 0) $sinVista = $b;
if ($sinVista !== null) comprobar('Sin línea de vista no se puede disparar', CombateLogica::disparar($e, 'friedrich', $sinVista), 'No tiene línea de vista.');
comprobar('A un compañero no se le dispara', CombateLogica::disparar($e, 'viktor', 'friedrich'), 'Ese no es un blanco válido.');
$blanco = null;
foreach (['maton_1', 'maton_2', 'maton_3'] as $b) if (CombateLogica::probabilidad($e, 'viktor', $b) > 0) { $blanco = $b; break; }
$vida = $e['unidades'][$blanco]['vida'];
comprobar('Viktor dispara', CombateLogica::disparar($e, 'viktor', $blanco), null);
$ev = end($e['eventos']);
comprobar('El disparo queda en los eventos y, si acierta, saca una vida', [$ev['tipo'], $e['unidades'][$blanco]['vida'] === $vida - ($ev['acierto'] ? 1 : 0)], ['disparo', true]);
comprobar('Después de actuar, no puede disparar otra vez', CombateLogica::disparar($e, 'viktor', $blanco), 'Ese personaje ya actuó en este turno.');
$antes = CombateLogica::probabilidad($e, 'maton_3', 'friedrich');
CombateLogica::cubrirse($e, 'friedrich');
$despues = CombateLogica::probabilidad($e, 'maton_3', 'friedrich');
comprobar('Cubrirse le baja 15 a quien le dispare (si tenía tiro)', $antes === 0 || $despues === max(5, $antes - 15), true);

echo "\nTurnos\n";
$e = $nuevo();
CombateLogica::terminarTurno($e);
$deRivales = array_filter($e['eventos'], fn($x) => in_array($x['tipo'], ['mover', 'disparo', 'cubrirse'], true) && str_starts_with($x['unidad'], 'maton'));
comprobar('Al terminar tu turno juegan los rivales', count($deRivales) > 0, true);
comprobar('Y vuelve a ser tu turno, ronda 2, con los personajes listos', $e['resultado'] !== null || ($e['turno'] === 'aliado' && $e['ronda'] === 2 && !$e['unidades']['viktor']['movio']), true);
$a = $nuevo(42); $b = $nuevo(42);
CombateLogica::terminarTurno($a); CombateLogica::terminarTurno($b);
comprobar('Misma semilla y mismas acciones, mismo resultado (nada librado al navegador)', $a['unidades'], $b['unidades']);
$e = $nuevo(5);
for ($k = 0; $k < 6 && $e['resultado'] === null; $k++) CombateLogica::terminarTurno($e);
comprobar('Sin hacer nada: o cae Viktor, o resiste las 5 rondas y gana', in_array($e['resultado'], ['derrota', 'victoria'], true) && ($e['resultado'] === 'derrota' || $e['ronda'] === 6), true);

echo "\nEquilibrio (300 combates simulados)\n";
$jugar = function (int $semilla, bool $activo) use ($nuevo) {
    $e = $nuevo($semilla);
    while ($e['resultado'] === null) {
        if ($activo) foreach (['viktor', 'friedrich'] as $id) if ($e['resultado'] === null && !$e['unidades'][$id]['caido']) CombateLogica::jugarUnidad($e, $id);
        if ($e['resultado'] === null) CombateLogica::terminarTurno($e);
    }
    return $e['resultado'];
};
$ganadas = 0; $pasivas = 0;
for ($s = 1; $s <= 300; $s++) { $ganadas += $jugar($s, true) === 'victoria' ? 1 : 0; $pasivas += $jugar($s, false) === 'victoria' ? 1 : 0; }
echo "      jugando con criterio: gana el " . round($ganadas / 3) . "% · sin hacer nada: el " . round($pasivas / 3) . "%\n";
comprobar('Jugando con criterio se gana la mayoría, pero no siempre (entre 60% y 97%)', $ganadas >= 180 && $ganadas <= 291, true);
comprobar('Sin hacer nada se gana bastante menos', $pasivas < $ganadas - 30, true);

echo "\nReglas del servidor\n";
$c = new CampanaLogica();
comprobar('Sin empezar el capítulo no hay combate', $c->combateIniciar(AURA, 'prologo', 'estacion')['exito'], false);
$c->empezar(AURA, 'prologo');
foreach (['taller', 'estacion', 'tiroteo'] as $punto) $c->guardar(AURA, 'prologo', $punto);
comprobar('Sin ganar el combate no se puede seguir (guardar la huida)', $c->guardar(AURA, 'prologo', 'huida')['mensaje'] ?? null, 'Primero hay que ganar el combate.');
comprobar('Un combate que no existe, no', $c->combateIniciar(AURA, 'prologo', 'inventado')['exito'], false);
$r = $c->combateIniciar(AURA, 'prologo', 'estacion');
comprobar('Empieza, y la respuesta no trae la semilla', [$r['exito'], isset($r['combate']['semilla'])], [true, false]);
comprobar('Una acción inválida se rechaza', $c->combateAccion(AURA, 'prologo', 'estacion', 'viktor', 'mover', 0, 0, '')['exito'], false);
comprobar('Mover a un rival, también', $c->combateAccion(AURA, 'prologo', 'estacion', 'maton_1', 'mover', 21, 5, '')['exito'], false);
$intentos = 0;
do {
    $intentos++;
    if ($intentos > 1) $c->combateIniciar(AURA, 'prologo', 'estacion');
    $estado = null;
    for ($k = 0; $k < 12; $k++) {
        $fila = (new FachadaPersistencia())->retornoIPersistenciaCampana()->combate(AURA, 'prologo', 'estacion')['estado'];
        if ($fila['resultado'] !== null) break;
        foreach (['viktor', 'friedrich'] as $id) {
            if ($fila['unidades'][$id]['caido'] || $fila['resultado'] !== null) continue;
            $copia = $fila; $copia['eventos'] = [];
            CombateLogica::jugarUnidad($copia, $id);   // elige como la IA y lo pide por la API, como haría el navegador
            foreach ($copia['eventos'] as $ev) {
                if ($ev['unidad'] !== $id) continue;
                $res = match ($ev['tipo']) {
                    'mover' => $c->combateAccion(AURA, 'prologo', 'estacion', $id, 'mover', $ev['a'][0], $ev['a'][1], ''),
                    'disparo' => $c->combateAccion(AURA, 'prologo', 'estacion', $id, 'disparar', 0, 0, $ev['blanco']),
                    'cubrirse' => $c->combateAccion(AURA, 'prologo', 'estacion', $id, 'cubrirse', 0, 0, ''),
                    default => null,
                };
                if ($res !== null) $fila = (new FachadaPersistencia())->retornoIPersistenciaCampana()->combate(AURA, 'prologo', 'estacion')['estado'];
            }
        }
        if ($fila['resultado'] === null) $c->combateTurno(AURA, 'prologo', 'estacion');
    }
    $resultado = (new FachadaPersistencia())->retornoIPersistenciaCampana()->combate(AURA, 'prologo', 'estacion')['resultado'];
} while ($resultado !== 'victoria' && $intentos < 20);
comprobar('Jugado por la API, se gana (con reintentos si hace falta)', $resultado, 'victoria');
comprobar('Los intentos quedan contados', (int) $db->query("SELECT Intentos FROM CAMPANA_COMBATES WHERE IDUsuario = " . AURA)->fetchColumn(), $intentos);
comprobar('Ganado, ya se puede seguir', $c->guardar(AURA, 'prologo', 'huida')['exito'], true);
$c->combateIniciar(AURA, 'prologo', 'estacion');
comprobar('Volver a jugarlo no borra la victoria', (new FachadaPersistencia())->retornoIPersistenciaCampana()->combate(AURA, 'prologo', 'estacion')['resultado'], 'victoria');

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
