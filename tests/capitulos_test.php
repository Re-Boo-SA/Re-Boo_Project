<?php
/*
 * Tests de los capítulos del Acto I (campaña): guiones, interrogatorio, persecución en auto,
 * la partida en la mesa de Otto y las reglas del servidor. Correr con la base RECIÉN IMPORTADA.
 *     php tests/capitulos_test.php
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
$c = new CampanaLogica();
$pl = new PartidaLogica();
const AURA = 1;
$raiz = dirname(__DIR__);
$aplanar = function (array $pasos) use (&$aplanar) { $r = []; foreach ($pasos as $p) { $r[] = $p; if ($p['tipo'] === 'si') $r = array_merge($r, $aplanar($p['pasos']));
    if ($p['tipo'] === 'paseo') foreach ($p['puntos'] as $pt) $r = array_merge($r, $aplanar($pt['pasos'])); } return $r; };

echo "\nGuiones\n";
$tipos = ['escena', 'fundido', 'tarjeta', 'franjas', 'dialogo', 'elegir', 'si', 'mover', 'esperar_mover', 'aparecer', 'objetivo', 'interactuar', 'sigilo', 'persecucion',
    'efecto', 'camara', 'guardar', 'fin', 'combate', 'partida', 'interrogatorio', 'persecucion_auto', 'tumbar', 'cerradura', 'agujas', 'vestir', 'paseo', 'desaparecer', 'seguir'];
$decisiones = [];
foreach (CampanaLogica::capitulos() as $cap) {
    if (!$cap['disponible']) continue;
    foreach ($aplanar(CampanaLogica::guion($cap['id'])['pasos']) as $p) {
        if ($p['tipo'] === 'elegir') $decisiones[$p['clave']] = array_column($p['opciones'], 'valor');
        if ($p['tipo'] === 'sigilo' && isset($p['registra'])) $decisiones[$p['registra']] = ['si', 'no'];
    }
}
$combates = [];
foreach (['capitulo_1', 'capitulo_2', 'capitulo_3', 'capitulo_4', 'capitulo_5', 'capitulo_6', 'capitulo_7', 'capitulo_8', 'capitulo_9', 'capitulo_10', 'capitulo_11', 'capitulo_12', 'capitulo_13', 'capitulo_14', 'capitulo_15', 'capitulo_16', 'capitulo_17', 'capitulo_18', 'capitulo_19'] as $id) {
    $cap = $c->capitulo(AURA, $id);
    $errores = [];
    $escena = null; $obj = []; $enEscena = [];
    $solido = function (array $m, float $x, float $y) {
        $col = array_values(array_filter($m['layers'], fn($l) => $l['name'] === 'colision'))[0]['data'];
        foreach ([[-10, 0], [10, 0], [-10, -8], [10, -8]] as [$dx, $dy]) {
            $cx = (int) floor(($x + $dx) / 40); $cy = (int) floor(($y + $dy) / 40);
            if ($cx < 0 || $cy < 0 || $cx >= $m['width'] || $cy >= $m['height'] || $col[$cy * $m['width'] + $cx]) return true;
        }
        return false;
    };
    $alcanzable = function (array $m, array $a, array $b, int $tolerancia = 0) {   // con tolerancia: llegar a menos de esos px del punto
        $col = array_values(array_filter($m['layers'], fn($l) => $l['name'] === 'colision'))[0]['data'];
        $w = $m['width']; $h = $m['height'];
        $ini = [intdiv((int) $a['x'], 40), intdiv((int) $a['y'], 40)]; $fin = [intdiv((int) $b['x'], 40), intdiv((int) $b['y'], 40)];
        $vistos = [$ini[1] * $w + $ini[0] => true]; $cola = [$ini];
        while ($cola) {
            $p = array_shift($cola);
            if ($p === $fin) return true;
            if ($tolerancia > 0 && hypot($p[0] * 40 + 20 - $b['x'], $p[1] * 40 + 20 - $b['y']) <= $tolerancia) return true;
            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                $q = [$p[0] + $dx, $p[1] + $dy];
                if ($q[0] < 0 || $q[1] < 0 || $q[0] >= $w || $q[1] >= $h || $col[$q[1] * $w + $q[0]] || isset($vistos[$q[1] * $w + $q[0]])) continue;
                $vistos[$q[1] * $w + $q[0]] = true; $cola[] = $q;
            }
        }
        return false;
    };
    foreach ($aplanar($cap['guion']['pasos']) as $i => $p) {
        if (!in_array($p['tipo'], $tipos, true)) $errores[] = "paso $i: tipo {$p['tipo']}";
        if ($p['tipo'] === 'escena') {
            $escena = $cap['mapas'][$p['mapa']] ?? null;
            if ($escena === null) { $errores[] = "falta el mapa {$p['mapa']}"; continue; }
            $obj = array_column(array_values(array_filter($escena['layers'], fn($l) => $l['name'] === 'objetos'))[0]['objects'], null, 'name');
            $enEscena = array_merge($p['actores'], $p['jugador'] ? [$p['jugador']] : [], $p['props'] ?? []);
            $jugadorEscena = $p['jugador'] ?? 'viktor';
            foreach (array_merge($p['actores'], $p['jugador'] ? [$p['jugador']] : []) as $a) {
                if (!isset($obj[$a])) $errores[] = "{$p['mapa']}: falta $a";
                elseif ($solido($escena, $obj[$a]['x'], $obj[$a]['y'])) $errores[] = "{$p['mapa']}: $a arranca en algo sólido";
                if (!is_file("$raiz/Capa_Presentacion/img/campana/pj_" . explode('_', $a)[0] . '.png')) $errores[] = "falta el personaje de $a";
            }
            foreach ($p['props'] ?? [] as $pr) if (!isset($obj[$pr])) $errores[] = "{$p['mapa']}: falta el objeto $pr";
        }
        if ($p['tipo'] === 'mover' && !isset($obj[$p['a']])) $errores[] = "paso $i: no existe {$p['a']}";
        if (in_array($p['tipo'], ['interactuar'], true) && !in_array($p['objeto'], $enEscena, true)) $errores[] = "paso $i: {$p['objeto']} no está en la escena";
        if (in_array($p['tipo'], ['objetivo'], true) && !isset($obj[$p['zona']])) $errores[] = "paso $i: no existe la zona {$p['zona']}";
        if (in_array($p['tipo'], ['dialogo', 'partida', 'interrogatorio'], true)) {
            $pj = explode('_', $p['quien'])[0];
            if (!isset($cap['guion']['personajes'][$pj])) $errores[] = "paso $i: personaje sin nombre $pj";
            foreach (['neutral', 'enojado', $p['emocion'] ?? 'neutral'] as $emo) if (!is_file("$raiz/Capa_Presentacion/img/campana/retratos/{$pj}_{$emo}.png")) $errores[] = "falta el retrato {$pj}_{$emo}";
        }
        if ($p['tipo'] === 'si' && !in_array($p['valor'], $decisiones[$p['clave']] ?? [], true)) $errores[] = "paso $i: la decisión {$p['clave']}={$p['valor']} no existe en ningún capítulo";
        if ($p['tipo'] === 'sigilo') {
            if (!$alcanzable($escena, $obj[$jugadorEscena], $obj[$p['meta']])) $errores[] = "paso $i: la meta no se alcanza caminando";
            foreach ($p['guardias'] as $gd) {
                $pts = json_decode(array_values(array_filter($obj['ruta_' . $gd]['properties'], fn($x) => $x['name'] === 'puntos'))[0]['value'], true);
                for ($k = 1; $k < count($pts); $k++) for ($s = 0; $s <= 20; $s++) {
                    $x = $pts[$k - 1][0] + ($pts[$k][0] - $pts[$k - 1][0]) * $s / 20; $y = $pts[$k - 1][1] + ($pts[$k][1] - $pts[$k - 1][1]) * $s / 20;
                    if ($solido($escena, $x, $y)) { $errores[] = "paso $i: la ruta de $gd atraviesa algo sólido en ($x,$y)"; break 2; }
                }
            }
        }
        if ($p['tipo'] === 'aparecer') $enEscena = array_merge($enEscena, $p['actores']);
        if ($p['tipo'] === 'seguir') {
            if (!in_array($p['objetivo'], $enEscena, true)) $errores[] = "seguir: {$p['objetivo']} no está en la escena";
            if (!isset($obj[$p['ruta']])) $errores[] = "seguir: falta la ruta {$p['ruta']}";
            else foreach (json_decode(array_column($obj[$p['ruta']]['properties'], 'value', 'name')['puntos'], true) as [$rx, $ry])
                if ($solido($escena, $rx, $ry)) $errores[] = "seguir: la ruta pasa por algo sólido en ($rx,$ry)";
        }
        if ($p['tipo'] === 'paseo') {
            foreach ($p['puntos'] as $pt) {
                if (!in_array($pt['objeto'], $enEscena, true) && !isset($obj[$pt['objeto']])) $errores[] = "paseo: {$pt['objeto']} no está en la escena";
                // cada punto se tiene que poder alcanzar caminando (a menos de 70 px de un lugar libre al que se llega)
                elseif (!$alcanzable($escena, $obj[$jugadorEscena], $obj[$pt['objeto']], 60)) $errores[] = "paseo: a {$pt['objeto']} no se llega caminando";
            }
            if (!isset($obj[$p['fin']['actor']])) $errores[] = "paseo: {$p['fin']['actor']} no tiene lugar en el mapa";
            $enEscena[] = $p['fin']['actor'];
        }
        if ($p['tipo'] === 'vestir') {
            if (!in_array($p['actor'], $enEscena, true)) $errores[] = "paso $i: vestir a {$p['actor']}, que no está";
            if (!is_file("$raiz/Capa_Presentacion/img/campana/pj_{$p['como']}.png") || !isset($cap['guion']['personajes'][$p['como']])) $errores[] = "paso $i: falta el personaje {$p['como']}";
            $enEscena[] = $p['como'];
            if ($jugadorEscena === $p['actor']) $jugadorEscena = $p['como'];
        }
        if ($p['tipo'] === 'agujas') {
            foreach ($p['agujas'] as $k => $a) if (!in_array($a['palanca'], $enEscena, true)) $errores[] = "agujas: la palanca de $k no está en la escena";
            if (!isset($obj[$p['garita']])) $errores[] = 'agujas: falta la garita';
            if (CampanaLogica::destinoAgujas($p, []) === $p['meta']) $errores[] = 'agujas: ya está resuelto al empezar';
            $soluciones = 0; $ks = array_keys($p['agujas']);
            for ($m = 0; $m < (1 << count($ks)); $m++) { $e = []; foreach ($ks as $n => $k) $e[$k] = ($m >> $n) & 1 ? 'desvio' : 'recta'; if (CampanaLogica::destinoAgujas($p, $e) === $p['meta']) $soluciones++; }
            if ($soluciones === 0) $errores[] = 'agujas: no tiene solución';
            $agujasDef = $p;
        }
        if ($p['tipo'] === 'combate') {
            foreach ($p['unidades'] as $uid => $u) {
                if (!in_array($uid, $enEscena, true)) $errores[] = "combate {$p['id']}: $uid no está en la escena";
                if ($solido($escena, $u['celda'][0] * 40 + 20, $u['celda'][1] * 40 + 30)) $errores[] = "combate {$p['id']}: $uid arranca en una casilla con obstáculo";
            }
            $combates[$p['id']] = [$p, $escena];
        }
        if ($p['tipo'] === 'partida' && (int) $db->query('SELECT EsBot FROM JUGADORES WHERE IDUsuario = ' . (int) $p['bot'])->fetchColumn() !== 1) $errores[] = "paso $i: el rival no es un bot";
    }
    comprobar("$id: mapas, actores, objetos, lugares, retratos, decisiones, metas y rutas", $errores, []);
}

echo "\nCombates del rescate (Capítulo 4, 200 simulaciones cada uno)\n";
foreach (['rescate_adentro', 'rescate_salida', 'tren', 'almacen_salida', 'almacen_asalto', 'mansion', 'reunion_falk', 'archivo'] as $idc) {
    [$def, $mapa] = $combates[$idc];
    $ganadas = 0; $pasivas = 0;
    for ($sem = 1; $sem <= 200; $sem++) {
        foreach ([true, false] as $activo) {
            $e = CombateLogica::nuevo($def, $mapa, $sem);
            while ($e['resultado'] === null) {
                if ($activo) foreach (array_keys(array_filter($e['unidades'], fn($u) => $u['lado'] === 'aliado')) as $uid) if ($e['resultado'] === null && !$e['unidades'][$uid]['caido']) CombateLogica::jugarUnidad($e, $uid);
                if ($e['resultado'] === null) CombateLogica::terminarTurno($e);
            }
            if ($e['resultado'] === 'victoria') { if ($activo) $ganadas++; else $pasivas++; }
        }
    }
    echo "      $idc: con criterio gana el " . round($ganadas / 2) . "% · sin hacer nada el " . round($pasivas / 2) . "%\n";
    comprobar("$idc: se gana con criterio (55% a 97%) y bastante menos sin hacer nada", $ganadas >= 110 && $ganadas <= 194 && $pasivas < $ganadas - 20, true);
}

echo "\nAgujas (Capítulo 6)\n";
$db->exec("INSERT INTO USUARIOS (Correo, Contra, NombreUsuario, Rol) VALUES ('agujas@test.local', '!', 'prueba_agujas', 'jugador')");
$U6 = (int) $db->lastInsertId();
$db->exec("INSERT INTO JUGADORES (IDUsuario) VALUES ($U6)");
foreach (['prologo', 'capitulo_1', 'capitulo_2', 'capitulo_3', 'capitulo_4', 'capitulo_5'] as $previo) $db->exec("INSERT INTO CAMPANA_PROGRESO (IDUsuario, IDCapitulo, Estado, PuntoGuardado) VALUES ($U6, '$previo', 'completado', 'final')");
comprobar('Con el 5 terminado, el 6 se puede empezar', $c->empezar($U6, 'capitulo_6')['exito'], true);
$c->guardar($U6, 'capitulo_6', 'plan'); $c->guardar($U6, 'capitulo_6', 'playa');
$c->interrogatorio($U6, 'capitulo_6', 'guardia_playa', [0, 0, 0]);
comprobar('Pasando frente al guardia se llega a las agujas', $c->guardar($U6, 'capitulo_6', 'agujas')['exito'], true);
comprobar('Sin acertar las agujas no se sigue', $c->guardar($U6, 'capitulo_6', 'tren')['exito'], false);
comprobar('Con agujas que faltan, se rechaza', $c->agujas($U6, 'capitulo_6', 'agujas_carga', ['A' => 'desvio'])['exito'], false);
$r = $c->agujas($U6, 'capitulo_6', 'agujas_carga', ['A' => 'recta', 'B' => 'desvio', 'C' => 'recta', 'D' => 'recta']);
comprobar('Como estaban: el tren se va a la estación', [$r['superado'], $r['destino']], [false, 'via_estacion']);
$r = $c->agujas($U6, 'capitulo_6', 'agujas_carga', ['A' => 'desvio', 'B' => 'recta', 'C' => 'recta', 'D' => 'recta']);
comprobar('Bien puestas: a la vía de carga', [$r['superado'], $r['destino']], [true, 'via_carga']);
comprobar('Y se puede seguir', $c->guardar($U6, 'capitulo_6', 'tren')['exito'], true);

echo "\nPersecución en auto (Capítulo 12, 150 huidas)\n";
$db->exec("INSERT INTO USUARIOS (Correo, Contra, NombreUsuario, Rol) VALUES ('huida@test.local', '!', 'prueba_huida', 'jugador')");
$U12 = (int) $db->lastInsertId();
$db->exec("INSERT INTO JUGADORES (IDUsuario) VALUES ($U12)");
foreach (array_merge(['prologo'], array_map(fn($n) => "capitulo_$n", range(1, 11))) as $previo) $db->exec("INSERT INTO CAMPANA_PROGRESO (IDUsuario, IDCapitulo, Estado, PuntoGuardado) VALUES ($U12, '$previo', 'completado', 'final')");
comprobar('Con el 11 terminado, el 12 se puede empezar', $c->empezar($U12, 'capitulo_12')['exito'], true);
$p12 = array_values(array_filter(CampanaLogica::guion('capitulo_12')['pasos'], fn($p) => $p['tipo'] === 'persecucion_auto'))[0];
$segura12 = fn($tr) => array_keys(array_column($tr['opciones'], 'riesgo'), min(array_column($tr['opciones'], 'riesgo')))[0];
$audaz12 = fn($tr) => array_keys(array_column($tr['opciones'], 'riesgo'), max(array_column($tr['opciones'], 'riesgo')))[0];
$g12 = ['segura' => 0, 'audaz' => 0];
for ($n = 0; $n < 150; $n++) {
    foreach (['segura' => $segura12, 'audaz' => $audaz12] as $nombre => $elegir) {
        $c->persecucionIniciar($U12, 'capitulo_12', 'huida_almacen');
        for ($k = 0; $k < count($p12['tramos']); $k++) {
            $r = $c->persecucionElegir($U12, 'capitulo_12', 'huida_almacen', $elegir($p12['tramos'][$k]));
            if ($r['persecucion']['resultado'] !== null) { if ($r['persecucion']['resultado'] === 'victoria') $g12[$nombre]++; break; }
        }
    }
}
echo "      a lo seguro escapa el " . round($g12['segura'] / 1.5) . "% · arriesgando el " . round($g12['audaz'] / 1.5) . "%\n";
comprobar('Se puede escapar, pero no siempre (entre 40% y 95% por cualquiera de los dos caminos)', $g12['segura'] >= 60 && $g12['segura'] <= 143 && $g12['audaz'] >= 60 && $g12['audaz'] <= 143, true);

echo "\nLos tres finales (Capítulo 19)\n";
$db->exec("INSERT INTO USUARIOS (Correo, Contra, NombreUsuario, Rol) VALUES ('final@test.local', '!', 'prueba_final', 'jugador')");
$U19 = (int) $db->lastInsertId();
$db->exec("INSERT INTO JUGADORES (IDUsuario) VALUES ($U19)");
foreach (array_merge(['prologo'], array_map(fn($n) => "capitulo_$n", range(1, 18))) as $previo) $db->exec("INSERT INTO CAMPANA_PROGRESO (IDUsuario, IDCapitulo, Estado, PuntoGuardado) VALUES ($U19, '$previo', 'completado', 'final')");
comprobar('Con el 18 terminado, el 19 se puede empezar', $c->empezar($U19, 'capitulo_19')['exito'], true);
foreach (CampanaLogica::puntos(CampanaLogica::guion('capitulo_19')) as $pt) $c->guardar($U19, 'capitulo_19', $pt);
comprobar('El final se elige entre los tres (y nada más)', [$c->decidir($U19, 'capitulo_19', 'final', 'vender')['exito'], $c->decidir($U19, 'capitulo_19', 'final', 'destruir')['exito']], [false, true]);
$db->exec("UPDATE CAMPANA_PROGRESO SET FechaInicio = NOW() - INTERVAL 10 MINUTE WHERE IDUsuario = $U19");
$r = $c->completar($U19, 'capitulo_19');
comprobar('Final C: el premio del capítulo, el logro de ese final y el aviso de que es el último', [$r['exito'], $r['xp'] ?? null, $r['logrosDecision'] ?? null, $r['siguiente'] ?? null, $r['ultimo'] ?? null], [true, 500, ['Nadie sale de Berlín'], null, true]);
$c->decidir($U19, 'capitulo_19', 'final', 'entregar');
$r = $c->completar($U19, 'capitulo_19');
comprobar('Volver a jugar con otro final: no paga de nuevo, pero da el logro del final A', [$r['yaCompletado'] ?? false, $r['xp'] ?? null, $r['logrosDecision'] ?? null], [true, null, ['La verdad']]);
$tiene = $db->query("SELECT GROUP_CONCAT(IDLogro ORDER BY IDLogro) FROM LOGROS_JUGADOR WHERE IDUsuario = $U19 AND IDLogro LIKE 'nacht_final_%'")->fetchColumn();
comprobar('Quedan los dos logros de final', $tiene, 'nacht_final_a,nacht_final_c');

echo "\nOrden de los capítulos\n";
comprobar('Sin terminar el prólogo, el capítulo 1 no se puede empezar', $c->empezar(AURA, 'capitulo_1')['mensaje'] ?? null, 'Primero terminá el capítulo anterior.');
$db->exec("INSERT INTO CAMPANA_PROGRESO (IDUsuario, IDCapitulo, Estado, PuntoGuardado) VALUES (" . AURA . ", 'prologo', 'completado', 'final')");
comprobar('Con el prólogo terminado, sí', $c->empezar(AURA, 'capitulo_1')['exito'], true);
comprobar('El 2 todavía no', $c->empezar(AURA, 'capitulo_2')['exito'], false);

echo "\nLa mesa de Otto (Capítulo 1)\n";
$c->empezar(AURA, 'capitulo_1');
foreach (['lista', 'club', 'mesa_otto'] as $pt) $c->guardar(AURA, 'capitulo_1', $pt);
comprobar('Sin ganarle a Otto no se sigue', $c->guardar(AURA, 'capitulo_1', 'final')['exito'], false);
comprobar('Todavía no se jugó', $c->partidaEstado(AURA, 'capitulo_1', 'mesa_otto')['estado'], 'sin_empezar');
$r = $c->partidaIniciar(AURA, 'capitulo_1', 'mesa_otto');
$id1 = $r['idPartida'] ?? 0;
$jug = $db->query("SELECT u.NombreUsuario FROM PARTICIPACIONES pa JOIN USUARIOS u ON u.IDUsuario = pa.IDUsuario WHERE pa.IDPartida = $id1 ORDER BY u.NombreUsuario")->fetchAll(PDO::FETCH_COLUMN);
comprobar('Una partida de verdad contra el bot de Otto, de una ronda', [$jug, $pl->vista($id1, AURA)['config']['rondas']], [['AURA', 'Otto Weiss'], 1]);
comprobar('Está en juego', $c->partidaEstado(AURA, 'capitulo_1', 'mesa_otto')['estado'], 'en_juego');
comprobar('Al terminar, vuelve a la historia (no a Berlín)', (new MisionLogica())->capituloDeDesafio($id1), 'capitulo_1');
comprobar('Pedirla de nuevo con la partida en juego, la retoma', $c->partidaIniciar(AURA, 'capitulo_1', 'mesa_otto')['idPartida'] ?? null, $id1);
$pl->accion($id1, AURA, 'salir', []);
comprobar('Si te vas de la mesa, perdiste', $c->partidaEstado(AURA, 'capitulo_1', 'mesa_otto')['estado'], 'perdida');
$id2 = $c->partidaIniciar(AURA, 'capitulo_1', 'mesa_otto')['idPartida'] ?? 0;
$db->exec("UPDATE PARTIDAS SET Estado = 'finalizada', FechaFin = NOW() WHERE IDPartida = $id2");
$db->exec("UPDATE PARTICIPACIONES SET Posicion = IF(IDUsuario = " . AURA . ", 1, 2) WHERE IDPartida = $id2");
comprobar('Ganándole, Otto habla', $c->partidaEstado(AURA, 'capitulo_1', 'mesa_otto')['estado'], 'ganada');
comprobar('Y se puede seguir', $c->guardar(AURA, 'capitulo_1', 'final')['exito'], true);
$db->exec("UPDATE CAMPANA_PROGRESO SET FechaInicio = NOW() - INTERVAL 10 MINUTE WHERE IDUsuario = " . AURA);
$xp = (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn();
$r = $c->completar(AURA, 'capitulo_1');
comprobar('Capítulo 1 terminado: 250 XP, su logro y el capítulo siguiente', [(int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn() - $xp, $r['logro'], $r['siguiente']['id']],
    [250, 'El dinero no tiene patria', 'capitulo_2']);
echo "\nInterrogatorio (Capítulo 2)\n";
$inter = null; $pers = null;
foreach (CampanaLogica::guion('capitulo_2')['pasos'] as $p) { if ($p['tipo'] === 'interrogatorio') $inter = $p; if ($p['tipo'] === 'persecucion_auto') $pers = $p; }
$min = array_sum(array_map(fn($q) => min(array_column($q['opciones'], 'sospecha')), $inter['preguntas']));
$max = array_sum(array_map(fn($q) => max(array_column($q['opciones'], 'sospecha')), $inter['preguntas']));
comprobar('Contestando bien se pasa, y contestando mal no', [$min < $inter['limite'], $max >= $inter['limite']], [true, true]);
comprobar('Sin empezar el capítulo, no hay interrogatorio', $c->interrogatorio(AURA, 'capitulo_2', 'control_anden', [0, 0, 0, 1])['exito'], false);
comprobar('Con el 1 terminado, el 2 se puede empezar', $c->empezar(AURA, 'capitulo_2')['exito'], true);
foreach (['taller', 'anden'] as $pt) $c->guardar(AURA, 'capitulo_2', $pt);
comprobar('Sin pasar el control no se sigue', $c->guardar(AURA, 'capitulo_2', 'playa')['mensaje'] ?? null, 'Primero hay que superar esa parte de la historia.');
comprobar('Faltan respuestas', $c->interrogatorio(AURA, 'capitulo_2', 'control_anden', [0, 0])['exito'], false);
comprobar('Una respuesta inventada', $c->interrogatorio(AURA, 'capitulo_2', 'control_anden', [0, 0, 9, 0])['exito'], false);
$r = $c->interrogatorio(AURA, 'capitulo_2', 'control_anden', [-1, -1, -1, -1]);
comprobar('No contestar nunca: 4 × 25 de sospecha y te detienen', [$r['sospecha'], $r['superado']], [100, false]);
$r = $c->interrogatorio(AURA, 'capitulo_2', 'control_anden', [2, 2, 2, 2]);
comprobar('Contestando lo peor: te detienen', $r['superado'], false);
$r = $c->interrogatorio(AURA, 'capitulo_2', 'control_anden', [0, 0, 0, 1]);
comprobar('Contestando lo mejor: pasás con poca sospecha', [$r['superado'], $r['sospecha']], [true, 15]);
comprobar('Pasado el control, se sigue', $c->guardar(AURA, 'capitulo_2', 'playa')['exito'], true);
$c->interrogatorio(AURA, 'capitulo_2', 'control_anden', [2, 2, 2, 2]);
comprobar('Repetirlo mal después no borra que lo pasaste', (new FachadaPersistencia())->retornoIPersistenciaCampana()->combate(AURA, 'capitulo_2', 'control_anden')['resultado'], 'victoria');

echo "\nPersecución en auto (Capítulo 2)\n";
$c->guardar(AURA, 'capitulo_2', 'huida');
comprobar('Sin escapar no se sigue', $c->guardar(AURA, 'capitulo_2', 'fotos')['exito'], false);
comprobar('Elegir sin empezar', $c->persecucionElegir(AURA, 'capitulo_2', 'huida_mitte', 0)['exito'] ?? null, false);
$e = $c->persecucionIniciar(AURA, 'capitulo_2', 'huida_mitte');
comprobar('Empieza con ventaja 2 y 4 tramos', [$e['persecucion']['ventaja'], $e['persecucion']['tramos']], [2, 4]);
comprobar('Una opción que no existe', $c->persecucionElegir(AURA, 'capitulo_2', 'huida_mitte', 7)['exito'], false);
$r = $c->persecucionElegir(AURA, 'capitulo_2', 'huida_mitte', -1);
comprobar('Quedarse sin decidir: se pierden 2 de ventaja (y con 2, te alcanzan)', [$r['persecucion']['cambio'], $r['persecucion']['resultado']], [-2, 'derrota']);
$jugar = function (callable $elegir) use ($c, $pers) {
    $c->persecucionIniciar(AURA, 'capitulo_2', 'huida_mitte');
    for ($k = 0; $k < 4; $k++) {
        $r = $c->persecucionElegir(AURA, 'capitulo_2', 'huida_mitte', $elegir($k, $pers['tramos'][$k]));
        if ($r['persecucion']['resultado'] !== null) return $r['persecucion']['resultado'];
    }
    return 'sin_terminar';
};
$segura = fn($k, $tr) => array_keys(array_column($tr['opciones'], 'riesgo'), min(array_column($tr['opciones'], 'riesgo')))[0];
$audaz = fn($k, $tr) => array_keys(array_column($tr['opciones'], 'riesgo'), max(array_column($tr['opciones'], 'riesgo')))[0];
$conRadio = fn($k, $tr) => $k === 1 ? 0 : $segura($k, $tr);
$ganadas = ['segura' => 0, 'audaz' => 0, 'radio' => 0];
for ($n = 0; $n < 150; $n++) {
    $ganadas['segura'] += $jugar($segura) === 'victoria' ? 1 : 0;
    $ganadas['audaz'] += $jugar($audaz) === 'victoria' ? 1 : 0;
    $ganadas['radio'] += $jugar($conRadio) === 'victoria' ? 1 : 0;
}
echo '      se escapa: eligiendo lo más seguro el ' . round($ganadas['segura'] / 1.5) . '% · lo más arriesgado el ' . round($ganadas['audaz'] / 1.5) . '% · con la radio de Thomas el ' . round($ganadas['radio'] / 1.5) . "%\n";
comprobar('Se puede escapar, pero no siempre (entre 40% y 95% con criterio)', $ganadas['segura'] >= 60 && $ganadas['segura'] <= 142, true);
comprobar('Escapado, se sigue', $c->guardar(AURA, 'capitulo_2', 'fotos')['exito'], true);

$db->exec("UPDATE CAMPANA_PROGRESO SET FechaInicio = NOW() - INTERVAL 10 MINUTE WHERE IDUsuario = " . AURA);   // mínimo de tiempo jugado
$c->guardar(AURA, 'capitulo_2', 'final');
$r = $c->completar(AURA, 'capitulo_2');
comprobar('Capítulo 2 terminado: sigue el 3', [$r['exito'], $r['siguiente']['id'] ?? null], [true, 'capitulo_3']);

echo "\nCapítulo 3: La casa de los muertos\n";
comprobar('El 3 se puede empezar', $c->empezar(AURA, 'capitulo_3')['exito'], true);
foreach (CampanaLogica::puntos(CampanaLogica::guion('capitulo_3')) as $pt) $c->guardar(AURA, 'capitulo_3', $pt);
$c->decidir(AURA, 'capitulo_3', 'esperar_a_friedrich', 'esperar');
$db->exec("UPDATE CAMPANA_PROGRESO SET FechaInicio = NOW() - INTERVAL 10 MINUTE WHERE IDUsuario = " . AURA);
$r = $c->completar(AURA, 'capitulo_3');
comprobar('Termina el Acto I: 300 XP, su logro y sigue el 4', [$r['exito'], $r['xp'] ?? null, $r['logro'] ?? null, $r['siguiente']['id'] ?? null], [true, 300, 'La casa de los muertos', 'capitulo_4']);
comprobar('La decisión sobre Friedrich queda guardada para lo que sigue', (new FachadaPersistencia())->retornoIPersistenciaCampana()->decisiones(AURA)['esperar_a_friedrich'] ?? null, 'esperar');

echo "\nCapítulo 4: el rescate según la rama\n";
foreach (['prologo', 'capitulo_1', 'capitulo_2', 'capitulo_3'] as $previo) $db->exec("INSERT IGNORE INTO CAMPANA_PROGRESO (IDUsuario, IDCapitulo, Estado, PuntoGuardado) VALUES (" . AURA . ", '$previo', 'completado', 'final')");
$TESTEO = (int) $db->query("SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario = 'testeo'")->fetchColumn();
foreach (['prologo', 'capitulo_1', 'capitulo_2', 'capitulo_3'] as $previo) $db->exec("INSERT IGNORE INTO CAMPANA_PROGRESO (IDUsuario, IDCapitulo, Estado, PuntoGuardado) VALUES ($TESTEO, '$previo', 'completado', 'final')");
foreach ([AURA => ['si', 'rescate_adentro', 'rescate_salida'], $TESTEO => ['no', 'rescate_salida', 'rescate_adentro']] as $u => [$desc, $suyo, $otro]) {
    $c->empezar($u, 'capitulo_4');
    $c->guardar($u, 'capitulo_4', 'sala');
    $c->interrogatorio($u, 'capitulo_4', 'falk_keller', [0, 0, 0, 0, 2]);
    foreach (['entrada', 'tiroteo'] as $pt) $c->guardar($u, 'capitulo_4', $pt);
    comprobar("Descubierto = $desc: sin ganar SU combate no se sigue", $c->guardar($u, 'capitulo_4', 'foto')['exito'], false);
    comprobar("Un sigilo que registra acepta si/no (y nada más)", [$c->decidir($u, 'capitulo_4', 'descubierto_rescate', 'quizas')['exito'], $c->decidir($u, 'capitulo_4', 'descubierto_rescate', $desc)['exito']], [false, true]);
    $c->combateIniciar($u, 'capitulo_4', $otro);
    $db->exec("UPDATE CAMPANA_COMBATES SET Resultado = 'victoria' WHERE IDUsuario = $u AND IDCombate = '$otro'");
    comprobar("Ganar el combate de la OTRA rama no sirve", $c->guardar($u, 'capitulo_4', 'foto')['exito'], false);
    $c->combateIniciar($u, 'capitulo_4', $suyo);
    $db->exec("UPDATE CAMPANA_COMBATES SET Resultado = 'victoria' WHERE IDUsuario = $u AND IDCombate = '$suyo'");
    comprobar("Ganando el de su rama ($suyo), sí", $c->guardar($u, 'capitulo_4', 'foto')['exito'], true);
}
$r = $c->interrogatorio(AURA, 'capitulo_4', 'falk_keller', [2, 1, 1, 1, 1]);
comprobar('Si Friedrich habla de más, Falk gana', $r['superado'], false);
$r = $c->interrogatorio(AURA, 'capitulo_4', 'falk_keller', [0, 0, 0, 0, 2]);
comprobar('Si no le da nada, aguanta', [$r['superado'], $r['sospecha']], [true, 25]);

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
