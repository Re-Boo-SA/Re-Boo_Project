<?php
/*
 * Tests de la campaña "Nacht über Berlin" (etapa D): guion, escenas y reglas del servidor.
 * Correr con la base RECIÉN IMPORTADA.
 *     php tests/campana_test.php
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
const AURA = 1;
$raiz = dirname(__DIR__);
if ((int) $db->query('SELECT COUNT(*) FROM CAMPANA_PROGRESO')->fetchColumn() > 0) {
    fwrite(STDERR, "Ya hay progreso de campaña: importá de nuevo la base y volvé a correr este test.\n");
    exit(2);
}

echo "\nGuion y escenas del prólogo\n";
$cap = $c->capitulo(AURA, 'prologo');
$g = $cap['guion'];
$tipos = ['escena', 'fundido', 'tarjeta', 'franjas', 'dialogo', 'elegir', 'si', 'mover', 'esperar_mover', 'aparecer', 'objetivo', 'interactuar', 'sigilo', 'persecucion', 'efecto', 'camara', 'guardar', 'fin', 'combate'];
$errores = [];
$escena = null;
$aplanar = function (array $pasos) use (&$aplanar) { $r = []; foreach ($pasos as $p) { $r[] = $p; if ($p['tipo'] === 'si') $r = array_merge($r, $aplanar($p['pasos'])); } return $r; };
$pasos = $aplanar($g['pasos']);
$objetos = fn(array $m) => array_values(array_filter($m['layers'], fn($l) => $l['name'] === 'objetos'))[0]['objects'];
$solido = function (array $m, float $x, float $y) {
    $col = array_values(array_filter($m['layers'], fn($l) => $l['name'] === 'colision'))[0]['data'];
    foreach ([[-10, 0], [10, 0], [-10, -8], [10, -8]] as [$dx, $dy]) {
        $cx = (int) floor(($x + $dx) / 40); $cy = (int) floor(($y + $dy) / 40);
        if ($cx < 0 || $cy < 0 || $cx >= $m['width'] || $cy >= $m['height'] || $col[$cy * $m['width'] + $cx]) return true;
    }
    return false;
};
$alcanzable = function (array $m, array $a, array $b) {
    $col = array_values(array_filter($m['layers'], fn($l) => $l['name'] === 'colision'))[0]['data'];
    $w = $m['width']; $h = $m['height'];
    $ini = [intdiv((int) $a['x'], 40), intdiv((int) $a['y'], 40)]; $fin = [intdiv((int) $b['x'], 40), intdiv((int) $b['y'], 40)];
    $vistos = [$ini[1] * $w + $ini[0] => true]; $cola = [$ini];
    while ($cola) {
        $p = array_shift($cola);
        if ($p === $fin) return true;
        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
            $q = [$p[0] + $dx, $p[1] + $dy];
            if ($q[0] < 0 || $q[1] < 0 || $q[0] >= $w || $q[1] >= $h || $col[$q[1] * $w + $q[0]] || isset($vistos[$q[1] * $w + $q[0]])) continue;
            $vistos[$q[1] * $w + $q[0]] = true; $cola[] = $q;
        }
    }
    return false;
};
$enEscena = [];
foreach ($pasos as $i => $p) {
    if (!in_array($p['tipo'], $tipos, true)) $errores[] = "paso $i: tipo {$p['tipo']}";
    if ($p['tipo'] === 'escena') {
        $escena = $cap['mapas'][$p['mapa']] ?? null;
        if ($escena === null) { $errores[] = "paso $i: falta el mapa {$p['mapa']}"; continue; }
        $obj = array_column($objetos($escena), null, 'name');
        $enEscena = array_merge($p['actores'], $p['jugador'] ? [$p['jugador']] : [], $p['props'] ?? []);
        foreach (array_merge($p['actores'], $p['jugador'] ? [$p['jugador']] : []) as $a) {
            if (!isset($obj[$a])) $errores[] = "{$p['mapa']}: falta la posición de $a";
            elseif ($solido($escena, $obj[$a]['x'], $obj[$a]['y'])) $errores[] = "{$p['mapa']}: $a arranca adentro de algo sólido";
            if (!is_file("$raiz/Capa_Presentacion/img/campana/pj_" . explode('_', $a)[0] . '.png')) $errores[] = "falta el personaje de $a";
        }
        foreach ($p['props'] ?? [] as $pr) if (!isset($obj[$pr])) $errores[] = "{$p['mapa']}: falta el objeto $pr";
    }
    $obj = $escena ? array_column($objetos($escena), null, 'name') : [];
    if (in_array($p['tipo'], ['mover'], true) && !isset($obj[$p['a']])) $errores[] = "paso $i: no existe el lugar {$p['a']}";
    if ($p['tipo'] === 'aparecer') { foreach ($p['actores'] as $a) { if (!isset($obj[$p['en'] ?? $a])) $errores[] = "paso $i: no hay dónde aparecer $a"; $enEscena[] = $a; } }
    if ($p['tipo'] === 'interactuar' && !in_array($p['objeto'], $enEscena, true)) $errores[] = "paso $i: el objeto {$p['objeto']} no está en la escena";
    if ($p['tipo'] === 'dialogo') {
        $personaje = explode('_', $p['quien'])[0];
        if (!isset($g['personajes'][$personaje])) $errores[] = "paso $i: personaje sin nombre $personaje";
        if (!is_file("$raiz/Capa_Presentacion/img/campana/retratos/{$personaje}_{$p['emocion']}.png")) $errores[] = "paso $i: falta el retrato {$personaje}_{$p['emocion']}";
    }
    if ($p['tipo'] === 'combate') {
        foreach ($p['unidades'] as $id => $u) {
            if (!in_array($id, $enEscena, true)) $errores[] = "combate: $id no está en la escena";
            if ($solido($escena, $u['celda'][0] * 40 + 20, $u['celda'][1] * 40 + 30)) $errores[] = "combate: $id arranca en una casilla con obstáculo";
        }
    }
    if (in_array($p['tipo'], ['sigilo', 'persecucion'], true)) {
        if (!isset($obj[$p['meta']])) $errores[] = "paso $i: falta la meta";
        elseif (!$alcanzable($escena, $obj['viktor'], $obj[$p['meta']])) $errores[] = "paso $i: la meta no se puede alcanzar caminando";
        foreach ($p['guardias'] ?? [] as $gd) {
            $ruta = $obj['ruta_' . $gd] ?? null;
            if ($ruta === null) { $errores[] = "paso $i: $gd no tiene ruta"; continue; }
            $pts = json_decode(array_values(array_filter($ruta['properties'], fn($x) => $x['name'] === 'puntos'))[0]['value'], true);
            for ($k = 1; $k < count($pts); $k++) {
                for ($s = 0; $s <= 20; $s++) {
                    $x = $pts[$k - 1][0] + ($pts[$k][0] - $pts[$k - 1][0]) * $s / 20; $y = $pts[$k - 1][1] + ($pts[$k][1] - $pts[$k - 1][1]) * $s / 20;
                    if ($solido($escena, $x, $y)) { $errores[] = "paso $i: la ruta de $gd atraviesa algo sólido en ($x,$y)"; break 2; }
                }
            }
        }
    }
}
comprobar('Todos los pasos son válidos: mapas, actores, objetos, lugares, retratos, metas alcanzables y rutas libres', $errores, []);
comprobar('Hay un aviso de ficción', mb_strlen($g['aviso']) > 20, true);
comprobar('Puntos de guardado en orden', CampanaLogica::puntos($g), ['taller', 'estacion', 'tiroteo', 'huida', 'escondite', 'final']);
comprobar('Un capítulo que no existe no se puede jugar', $c->capitulo(AURA, 'capitulo_20')['exito'], false);
comprobar('Pedir un archivo cualquiera no funciona', $c->capitulo(AURA, '../../config')['exito'], false);

echo "\nProgreso y reglas del servidor\n";
comprobar('Guardar sin empezar no se puede', $c->guardar(AURA, 'prologo', 'taller')['exito'], false);
comprobar('Empezar', $c->empezar(AURA, 'prologo')['progreso'], ['estado' => 'en_curso', 'punto' => null]);
comprobar('No se puede saltear un punto', $c->guardar(AURA, 'prologo', 'estacion')['exito'], false);
comprobar('Ni inventar uno', $c->guardar(AURA, 'prologo', 'cualquiera')['exito'], false);
comprobar('En orden, sí', [$c->guardar(AURA, 'prologo', 'taller')['exito'], $c->guardar(AURA, 'prologo', 'estacion')['exito']], [true, true]);
comprobar('Volver a un punto anterior no retrocede el progreso', [$c->guardar(AURA, 'prologo', 'taller')['exito'], $c->resumen(AURA)[0]['punto']], [true, 'estacion']);
comprobar('Una decisión que existe se guarda', $c->decidir(AURA, 'prologo', 'trato_anneliese', 'regatear')['exito'], true);
comprobar('Una que no existe, no', [$c->decidir(AURA, 'prologo', 'trato_anneliese', 'robarle')['exito'], $c->decidir(AURA, 'prologo', 'inventada', 'x')['exito']], [false, false]);
comprobar('Al volver a pedir el capítulo, vienen el progreso y las decisiones', [$c->capitulo(AURA, 'prologo')['progreso']['punto'], $c->capitulo(AURA, 'prologo')['decisiones']->trato_anneliese], ['estacion', 'regatear']);
comprobar('Terminar sin llegar al final no se puede', $c->completar(AURA, 'prologo')['exito'], false);
$c->guardar(AURA, 'prologo', 'tiroteo');
// El combate de la estación hay que ganarlo (acá se marca ganado directamente: el combate se prueba en combate_test.php)
$c->combateIniciar(AURA, 'prologo', 'estacion');
$db->exec("UPDATE CAMPANA_COMBATES SET Resultado = 'victoria' WHERE IDUsuario = " . AURA);
foreach (['huida', 'escondite', 'final'] as $punto) $c->guardar(AURA, 'prologo', $punto);
comprobar('Llegar al final en segundos tampoco (mínimo de tiempo jugado)', $c->completar(AURA, 'prologo')['mensaje'] ?? null, 'Fue demasiado rápido.');
$db->exec("UPDATE CAMPANA_PROGRESO SET FechaInicio = NOW() - INTERVAL 10 MINUTE WHERE IDUsuario = " . AURA);
$xp = (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn();
$r = $c->completar(AURA, 'prologo');
comprobar('Terminado: 200 XP y el logro', [$r['exito'], (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn() - $xp,
    (int) $db->query("SELECT COUNT(*) FROM LOGROS_JUGADOR WHERE IDUsuario = " . AURA . " AND IDLogro = 'nacht_prologo'")->fetchColumn()], [true, 200, 1]);
comprobar('Terminarlo de nuevo no vuelve a pagar', [$c->completar(AURA, 'prologo')['yaCompletado'] ?? false, (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn() - $xp], [true, 200]);
comprobar('Volver a jugarlo no cambia el progreso', [$c->empezar(AURA, 'prologo')['progreso']['estado'], $c->guardar(AURA, 'prologo', 'taller')['exito'], $c->resumen(AURA)[0]['estado']], ['completado', true, 'completado']);

echo "\nEntrada desde Berlín\n";
MundoLogica::$horaFija = 12.0;
SucesoLogica::$sucesoFijo = ['tipo' => null];
$TESTEO = (int) $db->query("SELECT IDUsuario FROM USUARIOS WHERE NombreUsuario = 'testeo'")->fetchColumn();
foreach (['periodista_1', 'periodista_2', 'periodista_3', 'periodista_4'] as $mi) {
    $db->exec("INSERT INTO MISIONES_JUGADOR (IDUsuario, IDMision, Estado, Paso) VALUES ($TESTEO, '$mi', 'completada', 3)");
}
$e = (new MisionLogica())->estado($TESTEO);
$ingrid = array_values(array_filter($e['contactos'], fn($x) => $x['id'] === 'periodista'))[0];
comprobar('Con el prólogo pendiente, Ingrid lleva la estrella de la historia', $ingrid['marca'], '★');
comprobar('El panel de misiones lista los capítulos', array_column($e['campana'], 'id'), ['prologo', 'capitulo_1', 'capitulo_2', 'capitulo_3', 'capitulo_4', 'capitulo_5', 'capitulo_6', 'capitulo_7', 'capitulo_8', 'capitulo_9', 'capitulo_10', 'capitulo_11', 'capitulo_12', 'capitulo_13', 'capitulo_14', 'capitulo_15', 'capitulo_16', 'capitulo_17', 'capitulo_18', 'capitulo_19']);
$e = (new MisionLogica())->estado(AURA);
comprobar('Con el prólogo terminado, el panel lo marca', $e['campana'][0]['estado'], 'completado');

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
