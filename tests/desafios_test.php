<?php
/*
 * Tests de misiones de tablero (Berlín vivo, etapa C): desafíos contra el bot de un contacto,
 * reglas especiales, escenarios y objetivos secundarios. Correr con la base RECIÉN IMPORTADA.
 *     php tests/desafios_test.php
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
MundoLogica::$horaFija = 12.0;
SucesoLogica::$sucesoFijo = ['tipo' => null];
$m = new MisionLogica();
$pl = new PartidaLogica();
$mundo = new MundoLogica();
const AURA = 1;
$datos = MisionLogica::datos();
if ((int) $db->query('SELECT COUNT(*) FROM MISION_PARTIDAS')->fetchColumn() > 0) {
    fwrite(STDERR, "Ya hay desafíos jugados: importá de nuevo la base y volvé a correr este test.\n");
    exit(2);
}

echo "\nContenido\n";
$errores = [];
$tipos = array_merge(...array_values(MotorJuego::TIPOS_DE_CATEGORIA));
foreach ($datos['misiones'] as $mi) {
    foreach ($mi['pasos'] as $p) {
        if ($p['tipo'] !== 'desafio') continue;
        $c = $datos['porContacto'][$p['contacto']];
        $bot = $db->query('SELECT u.NombreUsuario, j.EsBot FROM USUARIOS u JOIN JUGADORES j ON j.IDUsuario = u.IDUsuario WHERE u.IDUsuario = ' . (int) $c['bot'])->fetch(PDO::FETCH_ASSOC);
        if (!$bot || (int) $bot['EsBot'] !== 1 || $bot['NombreUsuario'] !== $c['nombre']) $errores[] = $mi['id'] . ': bot del contacto';
        if (($e = ConfigPartida::desdeArreglo(($p['reglas'] ?? []) + ['permiteBots' => 1])->validar()) !== null) $errores[] = $mi['id'] . ': reglas: ' . $e;
        foreach (['yo', 'rival'] as $lado) {
            foreach ($p['escenario'][$lado] ?? [] as $f) {
                if (!in_array($f['tipo'], $tipos, true) || !isset(MotorJuego::RECINTOS[$f['recinto']])) $errores[] = $mi['id'] . ': escenario ' . json_encode($f);
            }
        }
        if (isset($p['secundario']) && !in_array($p['secundario']['tipo'], ['recinto_min', 'muro_max', 'puntos_min'], true)) $errores[] = $mi['id'] . ': secundario';
    }
}
comprobar('Cada desafío tiene bot, reglas válidas, escenario y secundario válidos', $errores, []);
$libres = (new FachadaPersistencia())->retornoIPersistenciaPartidas()->botsLibres();
comprobar('Los bots de los contactos nunca aparecen como rivales al azar', array_values(array_filter($libres, fn($id) => $id > 104)), []);

echo "\nUn desafío completo (Paul: «Día de controles»)\n";
foreach (['obrero_1', 'obrero_2'] as $previa) {
    $db->exec("INSERT INTO MISIONES_JUGADOR (IDUsuario, IDMision, Estado, Paso) VALUES (" . AURA . ", '$previa', 'completada', 2)");
}
$mundo->elegirBando(AURA, 'oeste');
$mundo->mover(AURA, 0, 0, 2);
$irA = function (string $c) use ($db) { $p = NpcLogica::posicion($c, 12.0); $db->exec('UPDATE POSICIONES_MUNDO SET X = ' . ($p['x'] + 20) . ', Y = ' . $p['y'] . ' WHERE IDUsuario = ' . AURA); };
$irA('obrero');
comprobar('Paul ofrece su desafío', $m->hablar(AURA, 'obrero')['oferta']['id'] ?? null, 'obrero_3');
$m->aceptar(AURA, 'obrero_3');
$r = $m->hablar(AURA, 'obrero');
comprobar('Al hablarle, invita a jugar', [$r['desafio']['mision'] ?? null, $r['texto']], ['obrero_3', 'Hoy se juega a lo sólido. ¿Jugamos?']);
$db->exec('UPDATE POSICIONES_MUNDO SET X = 980, Y = 700 WHERE IDUsuario = ' . AURA);
comprobar('De lejos no se puede desafiar', $m->desafiar(AURA, 'obrero_3')['exito'], false);
$irA('obrero');
$d = $m->desafiar(AURA, 'obrero_3');
$id1 = $d['idPartida'] ?? 0;
$jugadores = $db->query("SELECT u.NombreUsuario FROM PARTICIPACIONES pa JOIN USUARIOS u ON u.IDUsuario = pa.IDUsuario WHERE pa.IDPartida = $id1 ORDER BY u.NombreUsuario")->fetchAll(PDO::FETCH_COLUMN);   // los asientos se sortean al empezar
comprobar('Crea una partida contra el bot de Paul', [$d['exito'], $jugadores], [true, ['AURA', 'Paul Lange']]);
$v = $pl->vista($id1, AURA);
comprobar('Con las reglas del desafío: sin espías ni cambio de ficha en el dado', [$v['estado'], $v['config']['caras']], ['en_curso', ['lado_izquierdo', 'lado_derecho', 'solo_militares', 'bloquea_fabrica']]);
comprobar('Pedirlo de nuevo con la partida en juego, la retoma', $m->desafiar(AURA, 'obrero_3')['idPartida'] ?? null, $id1);
comprobar('El sistema sabe que es un desafío', [$m->esDesafio($id1), $m->esDesafio(1)], [true, false]);

$pl->accion($id1, AURA, 'salir', []);
$e = $m->estado(AURA);
comprobar('Si abandonás, el desafío sigue pendiente', array_values(array_filter($e['activas'], fn($a) => $a['id'] === 'obrero_3'))[0]['paso'], 1);
comprobar('Y Paul te lo comenta', $m->hablar(AURA, 'obrero')['texto'], 'La obra no se gana en un día. ¿Revancha?');

$terminar = function (int $id, int $posicion, int $militares) use ($db) {
    $recinto = (int) $db->query("SELECT IDRecinto FROM RECINTOS WHERE Codigo = 'militares'")->fetchColumn();
    $db->exec("UPDATE FICHAS_PARTIDA SET Ubicacion = 'retirada', IDUsuario = NULL, IDRecinto = NULL WHERE IDPartida = $id AND IDUsuario = " . AURA);
    $nros = $db->query("SELECT NroFicha FROM FICHAS_PARTIDA WHERE IDPartida = $id AND Ubicacion = 'bolsa' LIMIT $militares")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($nros as $i => $nro) {
        $db->exec("UPDATE FICHAS_PARTIDA SET Ubicacion = 'tablero', IDUsuario = " . AURA . ", IDRecinto = $recinto, Posicion = $i, RondaColocada = 1, TurnoColocada = 1 WHERE IDPartida = $id AND NroFicha = $nro");
    }
    $db->exec("UPDATE PARTIDAS SET Estado = 'finalizada', FechaFin = NOW() WHERE IDPartida = $id");
    $db->exec("UPDATE PARTICIPACIONES SET Posicion = IF(IDUsuario = " . AURA . ", $posicion, 3 - $posicion), PuntajeFinal = 30 WHERE IDPartida = $id");
};
$id2 = $m->desafiar(AURA, 'obrero_3')['idPartida'] ?? 0;
comprobar('Se puede reintentar (partida nueva)', $id2 > $id1, true);
$terminar($id2, 1, 0);
comprobar('Ganar sin soldados en Militares no alcanza', $m->hablar(AURA, 'obrero')['texto'], 'Ganaste, pero sin soldados en Militares. Así no cuenta, camarada.');
$id3 = $m->desafiar(AURA, 'obrero_3')['idPartida'] ?? 0;
$terminar($id3, 2, 3);
comprobar('Perder, aunque llenes Militares, tampoco', $m->hablar(AURA, 'obrero')['texto'], 'La obra no se gana en un día. ¿Revancha?');
$id4 = $m->desafiar(AURA, 'obrero_3')['idPartida'] ?? 0;
$terminar($id4, 1, 2);
$xp = (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn();
$r = $m->hablar(AURA, 'obrero');
comprobar('Ganar con 2 en Militares cumple el desafío y al volver con Paul se completa', [in_array('Misión completada: Día de controles (+150 XP, +30 puntos de tienda)', $r['avisos'], true),
    (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn() - $xp], [true, 150]);
comprobar('Los intentos quedan registrados con su resultado', $db->query("SELECT Resultado FROM MISION_PARTIDAS WHERE IDUsuario = " . AURA . " ORDER BY IDPartida")->fetchAll(PDO::FETCH_COLUMN),
    ['abandonada', 'sin_secundario', 'perdida', 'ganada']);

echo "\nEscenario (Ingrid: «Dar vuelta la nota»)\n";
foreach (['periodista_1', 'periodista_2'] as $previa) {
    $db->exec("INSERT INTO MISIONES_JUGADOR (IDUsuario, IDMision, Estado, Paso) VALUES (" . AURA . ", '$previa', 'completada', 2)");
}
$irA('periodista');
$m->aceptar(AURA, 'periodista_4');
$id = $m->desafiar(AURA, 'periodista_4')['idPartida'] ?? 0;
$v = $pl->vista($id, AURA);
$yo = array_values(array_filter($v['jugadores'], fn($j) => $j['esYo']))[0];
$rival = array_values(array_filter($v['jugadores'], fn($j) => !$j['esYo']))[0];
comprobar('Una sola ronda', $v['config']['rondas'], 1);
comprobar('Arrancás con 2 fichas puestas y el rival con 5', [count($yo['tablero']), count($rival['tablero'])], [2, 5]);
comprobar('Y el rival arranca adelante en puntos', $rival['puntos']['total'] > $yo['puntos']['total'], true);
$recintos = array_column($rival['tablero'], 'recinto');
sort($recintos);
comprobar('Las fichas del escenario se ven en su recinto', $recintos, ['espias', 'espias', 'oeste', 'oeste', 'politicos']);

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
