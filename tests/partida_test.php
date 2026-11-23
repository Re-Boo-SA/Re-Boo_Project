<?php
/*
 * Test de integración de partidas en línea (usa la base de datos real).
 *     php tests/partida_test.php
 * OJO: crea partidas de prueba con AURA (1) y testeo (3). Pensado para una
 * base de desarrollo recién importada. Para no esperar los tiempos reales,
 * "adelanta el reloj" moviendo para atrás el inicio de la fase.
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
$logica = new PartidaLogica();
const AURA = 1;
const TESTEO = 3;

function adelantar(int $idPartida, int $segundos): void
{
    global $db;
    $db->prepare('UPDATE PARTIDAS SET InicioFase = InicioFase - INTERVAL :s SECOND WHERE IDPartida = :p')
        ->execute([':s' => $segundos, ':p' => $idPartida]);
}

function stats(int $id): array
{
    global $db;
    return $db->query("SELECT PntsPartida, PuntosHistoricos, PartidasJugadas, PartidasGanadas, MMR FROM JUGADORES WHERE IDUsuario = $id")->fetch(PDO::FETCH_ASSOC);
}

/** Hace la jugada de un humano: tira si le toca, mueve si debe, coloca la primera opción y confirma. */
function jugarHumano(PartidaLogica $logica, int $idPartida, int $id): ?array
{
    $v = $logica->vista($idPartida, $id);
    if (!$v['exito'] || $v['estado'] !== 'en_curso') {
        return $v;
    }
    $yo = $v['yo'];
    if (!$yo['activo']) {
        return $v;
    }
    if ($v['fase'] === 'dado') {
        if ($yo['esTirador']) {
            $logica->accion($idPartida, $id, 'tirar', []);
        }
        return $v;
    }
    if ($yo['confirmado']) {
        return $v;
    }
    if ($yo['debeMover'] && $yo['movibles'] !== []) {
        $m = $yo['movibles'][0];
        $r = $logica->accion($idPartida, $id, 'mover', ['nro' => $m['nro'], 'recinto' => $m['destinos'][0]]);
        if (!$r['exito']) {
            echo "      (mover falló: {$r['mensaje']})\n";
        }
    }
    if ($yo['colocada'] === null && !$yo['sinLugar']) {
        foreach ($yo['mano'] as $f) {
            $opciones = (array) $yo['opciones'];
            if (!empty($opciones[$f['tipo']])) {
                $r = $logica->accion($idPartida, $id, 'colocar', ['nro' => $f['nro'], 'recinto' => $opciones[$f['tipo']][0]]);
                if (!$r['exito']) {
                    echo "      (colocar falló: {$r['mensaje']})\n";
                }
                break;
            }
        }
    }
    $r = $logica->accion($idPartida, $id, 'confirmar', []);
    if (!$r['exito']) {
        echo "      (confirmar falló: {$r['mensaje']})\n";
    }
    return $v;
}

/** Juega hasta el final. $humanos = ids que juega el test; $alAvanzar se llama en cada vuelta. */
function jugarHastaElFinal(PartidaLogica $logica, int $idPartida, array $humanos, ?callable $alAvanzar = null): array
{
    for ($vuelta = 0; $vuelta < 400; $vuelta++) {
        foreach ($humanos as $id) {
            jugarHumano($logica, $idPartida, $id);
        }
        if ($alAvanzar) {
            $alAvanzar($vuelta);
        }
        adelantar($idPartida, 5); // deja jugar a los bots
        $v = $logica->vista($idPartida, $humanos[0]);
        if ($v['estado'] === 'finalizada') {
            return $v;
        }
    }
    return $v;
}

// Limpieza: por si quedaron partidas de una corrida anterior.
$db->exec("UPDATE PARTIDAS SET Estado = 'cancelada' WHERE Estado IN ('esperando', 'en_curso')");
$db->exec('DELETE FROM COLA_COMPETITIVA');

echo "\nPartida normal: AURA contra un bot\n";
$antes = stats(AURA);
$r = $logica->crearPartida(AURA, 'Test 1', 'publica', '', 2, 'normal');
comprobar('Se crea la sala', $r['exito'], true);
$id = $r['idPartida'];
comprobar('No se puede crear otra estando en una', $logica->crearPartida(AURA, 'Otra', 'publica', '', 2, 'normal')['exito'], false);
$v = $logica->vista($id, AURA);
comprobar('Arranca esperando', $v['estado'], 'esperando');
comprobar('El creador puede agregar bots', $v['puedeAgregarBot'], true);
$logica->accion($id, AURA, 'agregar_bot', []);
$v = $logica->vista($id, AURA);
comprobar('Con la sala llena arranca sola', $v['estado'], 'en_curso');
comprobar('Ronda 1, turno 1', [$v['ronda'], $v['turno']], [1, 1]);
comprobar('Recibo 6 fichas', count($v['yo']['mano']), 6);
$rival = array_values(array_filter($v['jugadores'], fn($j) => !$j['esYo']))[0];
comprobar('El rival es un bot y tiene 6 fichas', [$rival['esBot'], $rival['fichasEnMano']], [true, 6]);
comprobar('No veo la mano del bot (solo la cantidad)', isset($rival['mano']), false);

// Corrección con la X
while ($v['fase'] !== 'colocar') {
    jugarHumano($logica, $id, AURA);
    adelantar($id, 5);
    $v = $logica->vista($id, AURA);
}
$f = $v['yo']['mano'][0];
$opciones = (array) $v['yo']['opciones'];
$destino = $opciones[$f['tipo']][0] ?? null;
if ($destino !== null && !$v['yo']['debeMover']) {
    comprobar('Coloco una ficha', $logica->accion($id, AURA, 'colocar', ['nro' => $f['nro'], 'recinto' => $destino])['exito'], true);
    comprobar('No puedo colocar una segunda', $logica->accion($id, AURA, 'colocar', ['nro' => $v['yo']['mano'][1]['nro'], 'recinto' => 'muro'])['exito'], false);
    comprobar('La X la devuelve a la mano', $logica->accion($id, AURA, 'quitar', ['nro' => $f['nro']])['exito'], true);
    comprobar('Vuelvo a tener 6 en la mano', count($logica->vista($id, AURA)['yo']['mano']), 6);
}
comprobar('No puedo colocar una ficha que no es mía', $logica->accion($id, AURA, 'colocar', ['nro' => 999, 'recinto' => 'muro'])['exito'], false);

$fin = jugarHastaElFinal($logica, $id, [AURA]);
comprobar('La partida termina', $fin['estado'], 'finalizada');
$yo = array_values(array_filter($fin['jugadores'], fn($j) => $j['esYo']))[0];
comprobar('Termino con 12 fichas en el tablero', count($yo['tablero']), 12);
comprobar('El bot también', count($rival = array_values(array_filter($fin['jugadores'], fn($j) => !$j['esYo']))[0]['tablero']), 12);
comprobar('Hay posiciones finales', $yo['resultado']['posicion'] !== null, true);
comprobar('El historial tiene 24 colocaciones o más', count($fin['historial']) >= 24, true);
$despues = stats(AURA);
comprobar('Suma una partida jugada', $despues['PartidasJugadas'] - $antes['PartidasJugadas'], 1);
comprobar('Con bots no da puntos de tienda', $despues['PntsPartida'] - $antes['PntsPartida'], 0);

echo "\nPartida de 3: AURA, testeo y un bot · testeo abandona\n";
$r = $logica->crearPartida(AURA, 'Test 2', 'privada', 'clave123', 3, 'normal');
$id = $r['idPartida'];
comprobar('Contraseña incorrecta no deja entrar', $logica->unirse(TESTEO, $id, 'mal')['exito'], false);
comprobar('Contraseña correcta sí', $logica->unirse(TESTEO, $id, 'clave123')['exito'], true);
comprobar('testeo no puede agregar bots (no es el creador)', $logica->accion($id, TESTEO, 'agregar_bot', [])['exito'], false);
$logica->accion($id, AURA, 'completar_bots', []);
$v = $logica->vista($id, AURA);
comprobar('Arranca con 3', [$v['estado'], count($v['jugadores'])], ['en_curso', 3]);
comprobar('3 jugadores reciben 6 fichas cada uno de una bolsa de 46', count($v['yo']['mano']), 6);

// Información oculta: lo que coloco no lo ve testeo hasta que cierra el turno.
while ($v['fase'] !== 'colocar') {
    jugarHumano($logica, $id, AURA);
    jugarHumano($logica, $id, TESTEO);
    adelantar($id, 5);
    $v = $logica->vista($id, AURA);
}
$opciones = (array) $v['yo']['opciones'];
foreach ($v['yo']['mano'] as $f) {
    if (!empty($opciones[$f['tipo']]) && !$v['yo']['debeMover']) {
        $logica->accion($id, AURA, 'colocar', ['nro' => $f['nro'], 'recinto' => $opciones[$f['tipo']][0]]);
        break;
    }
}
$vistaTesteo = $logica->vista($id, TESTEO);
$auraParaTesteo = array_values(array_filter($vistaTesteo['jugadores'], fn($j) => $j['id'] === AURA))[0];
$auraParaAura = array_values(array_filter($logica->vista($id, AURA)['jugadores'], fn($j) => $j['id'] === AURA))[0];
comprobar('testeo no ve la ficha que AURA acaba de poner', count($auraParaTesteo['tablero']) < count($auraParaAura['tablero']), true);

$antesTesteo = stats(TESTEO);
$fin = jugarHastaElFinal($logica, $id, [AURA, TESTEO], function ($vuelta) use ($logica, $id) {
    if ($vuelta === 8) {
        $logica->accion($id, TESTEO, 'salir', []);
    }
});
$t = array_values(array_filter($fin['jugadores'], fn($j) => $j['id'] === TESTEO))[0];
comprobar('testeo figura como abandono', $t['estado'], 'abandono');
comprobar('testeo no tiene puesto', $t['resultado']['posicion'], null);
comprobar('testeo no recibe puntos', $t['resultado']['otorgados'], 0);
comprobar('La partida siguió y terminó', $fin['estado'], 'finalizada');

echo "\nPartida entre humanos: tiempo vencido y puntos\n";
$antesA = stats(AURA);
$antesT = stats(TESTEO);
$id = $logica->crearPartida(AURA, 'Test 3', 'publica', '', 2, 'normal')['idPartida'];
$logica->unirse(TESTEO, $id, '');
$v = $logica->vista($id, AURA);
comprobar('Dos humanos: arranca', $v['estado'], 'en_curso');
adelantar($id, 20); // nadie tiró el dado en 15 s
$v = $logica->vista($id, AURA);
comprobar('Si nadie tira, el dado se tira solo', $v['fase'], 'colocar');
adelantar($id, 70); // nadie colocó en 60 s
$v = $logica->vista($id, AURA);
comprobar('Si nadie juega, se juega solo y pasa el turno', $v['turno'], 2);
$hist = $v['historial'];
comprobar('El historial marca las jugadas automáticas', $hist !== [] && $hist[0]['tipo'] === 'auto', true);
$fin = jugarHastaElFinal($logica, $id, [AURA, TESTEO]);
$a = array_values(array_filter($fin['jugadores'], fn($j) => $j['id'] === AURA))[0];
$t = array_values(array_filter($fin['jugadores'], fn($j) => $j['id'] === TESTEO))[0];
$despuesA = stats(AURA);
$despuesT = stats(TESTEO);
comprobar('Sin bots: AURA suma sus puntos a la tienda', $despuesA['PntsPartida'] - $antesA['PntsPartida'], $a['resultado']['puntaje']);
comprobar('…y a los puntos históricos', $despuesA['PuntosHistoricos'] - $antesA['PuntosHistoricos'], $a['resultado']['puntaje']);
comprobar('testeo también', $despuesT['PntsPartida'] - $antesT['PntsPartida'], $t['resultado']['puntaje']);
$ganadas = ($despuesA['PartidasGanadas'] - $antesA['PartidasGanadas']) + ($despuesT['PartidasGanadas'] - $antesT['PartidasGanadas']);
comprobar('Al menos uno suma una victoria', $ganadas >= 1, true);

echo "\nPersonalizada: 1 ronda de 4 fichas, sin cara de espías\n";
$r = $logica->crearPartida(AURA, 'Test 4', 'publica', '', 2, 'personalizada', [
    'rondas' => 1, 'fichasPorRonda' => 4, 'caras' => ['lado_izquierdo', 'lado_derecho', 'cambiar_ficha'],
]);
comprobar('Se crea', $r['exito'], true);
$id = $r['idPartida'];
$logica->unirse(TESTEO, $id, '');
$caras = [];
$fin = jugarHastaElFinal($logica, $id, [AURA, TESTEO], function () use ($logica, $id, &$caras) {
    $v = $logica->vista($id, AURA);
    if (!empty($v['cara'])) {
        $caras[$v['cara']] = true;
    }
});
$a = array_values(array_filter($fin['jugadores'], fn($j) => $j['id'] === AURA))[0];
comprobar('Termina con 4 fichas por jugador', count($a['tablero']), 4);
comprobar('Nunca salió una cara deshabilitada', array_diff(array_keys($caras), ['lado_izquierdo', 'lado_derecho', 'cambiar_ficha']), []);
comprobar('Reglas imposibles se rechazan', $logica->crearPartida(AURA, 'X', 'publica', '', 5, 'personalizada', ['rondas' => 3, 'fichasPorRonda' => 8])['exito'], false);

echo "\nCompetitivo: cola y MMR\n";
$cola = new ColaLogica();
$mmrA = stats(AURA)['MMR'];
$mmrT = stats(TESTEO)['MMR'];
comprobar('AURA entra a la cola', $cola->entrar(AURA)['exito'], true);
$cola->entrar(TESTEO);
$e = $cola->estado(AURA);
comprobar('Con 2 no arma mesa enseguida', $e['enCola'], true);
$db->exec('UPDATE COLA_COMPETITIVA SET FechaIngreso = FechaIngreso - INTERVAL 70 SECOND');
$e = $cola->estado(AURA);
comprobar('A los 60 s arma una mesa chica', isset($e['idPartida']), true);
$id = $e['idPartida'];
comprobar('testeo también recibe la partida', $cola->estado(TESTEO)['idPartida'] ?? null, $id);
$v = $logica->vista($id, AURA);
comprobar('Es competitiva y ya arrancó', [$v['modo'], $v['estado']], ['competitiva', 'en_curso']);
$fin = jugarHastaElFinal($logica, $id, [AURA, TESTEO]);
$a = array_values(array_filter($fin['jugadores'], fn($j) => $j['id'] === AURA))[0];
comprobar('Se guardó el MMR de antes', $a['resultado']['mmrAntes'], $mmrA);
comprobar('El MMR cambió', stats(AURA)['MMR'] !== $mmrA || stats(TESTEO)['MMR'] !== $mmrT, true);
$suma = (stats(AURA)['MMR'] - $mmrA) + (stats(TESTEO)['MMR'] - $mmrT);
comprobar('Entre dos, lo que gana uno lo pierde el otro (±1 por redondeo)', abs($suma) <= 1, true);

echo "\nEmparejamiento (sin base)\n";
$mesa = ColaLogica::elegirMesa([
    ['id' => 1, 'mmr' => 1000, 'segundos' => 5], ['id' => 2, 'mmr' => 1050, 'segundos' => 4],
    ['id' => 3, 'mmr' => 1800, 'segundos' => 3], ['id' => 4, 'mmr' => 980, 'segundos' => 2], ['id' => 5, 'mmr' => 1100, 'segundos' => 1],
]);
comprobar('Arma 4 con MMR parecido y deja afuera al de 1800', $mesa, [1, 4, 2, 5]);
comprobar('Con 3 recién llegados espera', ColaLogica::elegirMesa([
    ['id' => 1, 'mmr' => 1000, 'segundos' => 5], ['id' => 2, 'mmr' => 1000, 'segundos' => 5], ['id' => 3, 'mmr' => 1000, 'segundos' => 5]]), null);
comprobar('Rango de 1000 = Cabo', RankingLogica::rango(1000)['nombre'], 'Cabo');
comprobar('Rango de 1750 = General', RankingLogica::rango(1750)['nombre'], 'General');

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
