<?php
/*
 * Tests de torneos relámpago (Fase 4). Usa la base real: correrlo con la base RECIÉN IMPORTADA.
 *     php tests/torneos_test.php
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
$tl = new TorneoLogica();
$pl = new PartidaLogica();
if ((int) $db->query('SELECT COUNT(*) FROM TORNEOS')->fetchColumn() > 0) {
    fwrite(STDERR, "Ya hay torneos: importá de nuevo la base y volvé a correr este test.\n");
    exit(2);
}
$hash = SeguridadLogica::hashPassword('Prueba2026!');
$u = [];
for ($i = 1; $i <= 14; $i++) {
    $n = sprintf('torneo%02d', $i);
    $db->prepare("INSERT INTO USUARIOS (Correo, Contra, NombreUsuario, Rol) VALUES (?, ?, ?, 'jugador')")->execute(["$n@test.local", $hash, $n]);
    $id = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO JUGADORES (IDUsuario, MMR) VALUES (?, ?)')->execute([$id, 1600 - $i * 10]);   // torneo01 es el de más MMR
    $u[$i] = $id;
}
$detalle = fn(int $t, int $quien) => $tl->detalle($quien, $t);
$llaves = fn(int $t) => $db->query("SELECT * FROM TORNEO_LLAVES WHERE IDTorneo = $t ORDER BY Ronda, Posicion")->fetchAll(PDO::FETCH_ASSOC);

echo "\nArmar un torneo\n";
comprobar('Nombre muy corto', $tl->crear($u[1], 'ab', 4, 15)['exito'], false);
comprobar('Tamaño que no existe', $tl->crear($u[1], 'Copa Kreuzberg', 5, 15)['exito'], false);
comprobar('Hora de inicio que no existe', $tl->crear($u[1], 'Copa Kreuzberg', 4, 7)['exito'], false);
$r = $tl->crear($u[1], 'Copa Kreuzberg', 4, 15);
$T1 = $r['idTorneo'] ?? 0;
comprobar('Se arma y quien lo arma queda inscripto', [$r['exito'], count($detalle($T1, $u[1])['jugadores'])], [true, 1]);
comprobar('Uno activo por vez', $tl->crear($u[1], 'Otra copa', 8, 30)['exito'], false);

echo "\nInscripción y arranque\n";
$tl->inscribir($u[3], $T1);
$tl->inscribir($u[2], $T1);
comprobar('No se inscribe dos veces', $tl->inscribir($u[2], $T1)['exito'], false);
comprobar('Antes de empezar se puede bajar', $tl->salir($u[3], $T1)['exito'], true);
$tl->inscribir($u[3], $T1);
comprobar('Todavía en inscripción con 3 de 4', $detalle($T1, $u[1])['torneo']['estado'], 'inscripcion');
$tl->inscribir($u[4], $T1);
$d = $detalle($T1, $u[1]);
comprobar('Al llenarse empieza solo', [$d['torneo']['estado'], $d['torneo']['ronda'], $d['torneo']['totalRondas']], ['en_curso', 1, 2]);
comprobar('Cuadro sembrado por MMR: 1 contra 4 y 2 contra 3', array_map(fn($c) => [$c['a'], $c['b']], $d['rondas'][0]), [['torneo01', 'torneo04'], ['torneo02', 'torneo03']]);
$L = $llaves($T1);
$partida0 = (int) $L[0]['IDPartida'];
$partida1 = (int) $L[1]['IDPartida'];
comprobar('Cada cruce es una partida en juego de 2', [$db->query("SELECT Estado FROM PARTIDAS WHERE IDPartida = $partida0")->fetchColumn(),
    (int) $db->query("SELECT COUNT(*) FROM PARTICIPACIONES WHERE IDPartida = $partida0")->fetchColumn()], ['en_curso', 2]);
comprobar('Ya no se puede inscribir nadie', $tl->inscribir($u[5], $T1)['exito'], false);
comprobar('torneo01 ve su partida pendiente', $tl->miPartidaPendiente($u[1])['idPartida'] ?? null, $partida0);
comprobar('La partida sabe de qué torneo es', $tl->torneoDePartida($partida0), $T1);

echo "\nRondas\n";
$pl->accion($partida0, $u[4], 'salir', []);
$d = $detalle($T1, $u[1]);
comprobar('Quien abandona pierde enseguida, pero la ronda espera al otro cruce', [$d['rondas'][0][0]['ganador'] ?? null, $d['torneo']['ronda'], count($d['rondas'])], [$u[1], 1, 1]);
comprobar('El que abandonó ya figura eliminado', in_array('torneo04', array_column(array_filter($d['jugadores'], fn($j) => $j['eliminado']), 'nombre'), true), true);
$pl->accion($partida1, $u[2], 'salir', []);
$d = $detalle($T1, $u[1]);
comprobar('Con los dos cruces cerrados, se arma la final', [$d['torneo']['ronda'], [$d['rondas'][1][0]['a'], $d['rondas'][1][0]['b']]], [2, ['torneo01', 'torneo03']]);
comprobar('Los eliminados quedan marcados', array_column(array_filter($d['jugadores'], fn($j) => $j['eliminado']), 'nombre'), ['torneo02', 'torneo04']);
$xpAntes = [(int) $db->query("SELECT XP FROM JUGADORES WHERE IDUsuario = {$u[1]}")->fetchColumn(), (int) $db->query("SELECT XP FROM JUGADORES WHERE IDUsuario = {$u[3]}")->fetchColumn()];
$final = (int) $llaves($T1)[2]['IDPartida'];
$pl->accion($final, $u[3], 'salir', []);
$d = $detalle($T1, $u[1]);
comprobar('Termina y corona al campeón', [$d['torneo']['estado'], $d['torneo']['ganador']], ['finalizado', 'torneo01']);
$xpDespues = [(int) $db->query("SELECT XP FROM JUGADORES WHERE IDUsuario = {$u[1]}")->fetchColumn(), (int) $db->query("SELECT XP FROM JUGADORES WHERE IDUsuario = {$u[3]}")->fetchColumn()];
comprobar('Premio: 200 XP al campeón y 80 al finalista', [$xpDespues[0] - $xpAntes[0], $xpDespues[1] - $xpAntes[1]], [200, 80]);
comprobar('El campeón recibe el logro', (int) $db->query("SELECT COUNT(*) FROM LOGROS_JUGADOR WHERE IDUsuario = {$u[1]} AND IDLogro = 'campeon_torneo'")->fetchColumn(), 1);
comprobar('El perfil cuenta los torneos ganados', (new SocialLogica())->perfil($u[2], $u[1])['perfil']['torneosGanados'], 1);
$tl->detalle($u[1], $T1);
comprobar('Mirar de nuevo no vuelve a pagar', (int) $db->query("SELECT XP FROM JUGADORES WHERE IDUsuario = {$u[1]}")->fetchColumn(), $xpDespues[0]);
comprobar('Con su torneo terminado, puede armar otro', $tl->listar($u[1])['puedoCrear'], true);

echo "\nFaltan jugadores a la hora de empezar\n";
$T2 = $tl->crear($u[5], 'Copa Mitte', 8, 15)['idTorneo'];
foreach ([6, 7, 8, 9] as $i) {
    $tl->inscribir($u[$i], $T2);
}
$db->exec("UPDATE TORNEOS SET FechaInicio = NOW() - INTERVAL 1 MINUTE WHERE IDTorneo = $T2");
$d = $detalle($T2, $u[5]);
$cruces = $d['rondas'][0];
comprobar('Con 5 de 8 arranca a la hora, en un cuadro de 8', [$d['torneo']['estado'], count($cruces)], ['en_curso', 4]);
comprobar('Los 3 mejores sembrados pasan sin jugar y hay un solo partido', [count(array_filter($cruces, fn($c) => $c['resultado'] === 'sin_rival')), count(array_filter($cruces, fn($c) => $c['idPartida'] !== null))], [3, 1]);
$T3 = $tl->crear($u[10], 'Copa vacía', 4, 15)['idTorneo'];
$db->exec("UPDATE TORNEOS SET FechaInicio = NOW() - INTERVAL 1 MINUTE WHERE IDTorneo = $T3");
comprobar('Si a la hora hay uno solo, se cancela', $detalle($T3, $u[10])['torneo']['estado'], 'cancelado');

echo "\nJugadores ocupados cuando empieza la ronda\n";
$T4 = $tl->crear($u[11], 'Copa Pankow', 4, 15)['idTorneo'];
$tl->inscribir($u[12], $T4);
$tl->inscribir($u[13], $T4);
$sala = $pl->crearPartida($u[12], 'Sala de torneo12', 'publica', '', 4, 'normal')['idPartida'];    // esperando gente
$otra = $pl->crearPartida($u[13], 'Partida de torneo13', 'publica', '', 2, 'normal')['idPartida'];
$pl->accion($otra, $u[13], 'completar_bots', []);                                               // jugando otra partida
$tl->inscribir($u[14], $T4);
$L = $llaves($T4);
comprobar('Quien esperaba en una sala la deja y juega su cruce', [$pl->partidaActiva($u[12]) !== $sala, (int) $db->query("SELECT COUNT(*) FROM PARTICIPACIONES WHERE IDPartida = $sala AND IDUsuario = {$u[12]} AND Estado = 'activo'")->fetchColumn()], [true, 0]);
comprobar('Quien está jugando otra partida pierde ese cruce por no presentarse', [$L[1]['Resultado'], (int) $L[1]['IDGanador']], ['no_se_presento', $u[12]]);

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
