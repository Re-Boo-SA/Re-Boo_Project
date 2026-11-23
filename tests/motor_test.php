<?php
/*
 * Tests del motor de reglas. Se corren por consola:
 *     php tests/motor_test.php
 * Cada caso sale de un ejemplo o una regla del manual.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../Capa_Logica/MotorJuego.php';

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
        echo "  ✘ $nombre\n      esperado: " . json_encode($esperado, JSON_UNESCAPED_UNICODE)
            . "\n      obtenido: " . json_encode($obtenido, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

/** Arma un tablero rápido: [['tipo', 'recinto'], ...] en orden de colocación. */
function tablero(MotorJuego $motor, array $fichas): array
{
    $t = [];
    foreach ($fichas as $i => [$tipo, $recinto]) {
        $t = $motor->colocar($t, $i + 1, $tipo, $recinto, 1, 1);
    }
    return $t;
}

$m = new MotorJuego();

echo "\nBolsa (sección 2.4)\n";
comprobar('2 jugadores = 50 fichas', array_sum(MotorJuego::composicionBolsa(2)), 50);
comprobar('3 jugadores = 46 fichas', array_sum(MotorJuego::composicionBolsa(3)), 46);
comprobar('4 jugadores = 50 fichas', array_sum(MotorJuego::composicionBolsa(4)), 50);
comprobar('5 jugadores = 60 fichas', array_sum(MotorJuego::composicionBolsa(5)), 60);
$b3 = MotorJuego::composicionBolsa(3);
comprobar('3 jugadores: se retiran 2 Markus y 2 Ursula', [$b3['markus'], $b3['ursula']], [3, 3]);
comprobar('3 jugadores: 1 de cada militar', [$b3['general'], $b3['oficial_militar'], $b3['tropa']], [5, 5, 5]);
comprobar('3 jugadores: 10 políticos', $b3['politico'], 10);
comprobar('La bolsa mezclada tiene la cantidad correcta', count(MotorJuego::armarBolsa(4)), 50);

echo "\nOeste, Este y Muro (1 punto por ficha)\n";
$t = tablero($m, [['cabo', 'oeste'], ['tropa', 'oeste'], ['markus', 'este'], ['peon', 'muro']]);
$p = $m->puntuar($t);
comprobar('Oeste con 2 fichas = 2', $p['recintos']['oeste'], 2);
comprobar('Este con 1 ficha = 1', $p['recintos']['este'], 1);
comprobar('Muro con 1 ficha = 1', $p['recintos']['muro'], 1);

echo "\nFábrica y Militares (1, 3 y 7 por orden de aparición)\n";
$p = $m->puntuar(tablero($m, [['cabo', 'fabrica'], ['peon', 'fabrica'], ['oficial_obrera', 'fabrica']]));
comprobar('Cabo, Peón, Oficial Obrera = 1+3+7 = 11', $p['recintos']['fabrica'], 11);
$p = $m->puntuar(tablero($m, [['peon', 'fabrica'], ['cabo', 'fabrica'], ['cabo', 'fabrica']]));
comprobar('Peón, Cabo, Cabo = 1+3+3 = 7 (la repetición conserva su valor)', $p['recintos']['fabrica'], 7);
$p = $m->puntuar(tablero($m, [['oficial_obrera', 'fabrica'], ['oficial_obrera', 'fabrica']]));
comprobar('El valor no está atado al nombre: Oficial Obrera primero vale 1', $p['recintos']['fabrica'], 2);
$p = $m->puntuar(tablero($m, [['general', 'militares'], ['tropa', 'militares'], ['oficial_militar', 'militares']]));
comprobar('General, Tropa, Oficial Militar = 11', $p['recintos']['militares'], 11);

echo "\nEspías (4 puntos con Markus y Ursula)\n";
comprobar('Solo Markus = 0', $m->puntuar(tablero($m, [['markus', 'espias']]))['recintos']['espias'], 0);
comprobar('Markus + Markus = 0', $m->puntuar(tablero($m, [['markus', 'espias'], ['markus', 'espias']]))['recintos']['espias'], 0);
comprobar('Markus + Ursula = 4', $m->puntuar(tablero($m, [['markus', 'espias'], ['ursula', 'espias']]))['recintos']['espias'], 4);

echo "\nPolíticos (1 punto cada uno, condición previa)\n";
comprobar('Sin político en Políticos no puede ir a Oeste',
    $m->motivoRecinto([], 'politico', 'oeste') !== null, true);
$t = tablero($m, [['politico', 'politicos']]);
comprobar('Con político en Políticos sí puede ir a Oeste', $m->motivoRecinto($t, 'politico', 'oeste'), null);
$t = tablero($m, [['politico', 'politicos'], ['politico', 'oeste'], ['politico', 'fabrica']]);
$p = $m->puntuar($t);
comprobar('Oeste con un político: 1 punto básico', $p['recintos']['oeste'], 1);
comprobar('Fábrica con un político: 0 básicos (no es un tipo de obrero)', $p['recintos']['fabrica'], 0);
comprobar('3 políticos = 3 puntos aparte', $p['politicos'], 3);
comprobar('Total = 1 (Oeste) + 3 (políticos) = 4', $p['total'], 4);
comprobar('Políticos tiene capacidad 1', $m->motivoRecinto($t, 'politico', 'politicos') !== null, true);
$sinRegla = new MotorJuego(ConfigPartida::desdeArreglo(['reglaPolitico' => false]));
comprobar('Personalizada sin regla del político: va a Oeste directo', $sinRegla->motivoRecinto([], 'politico', 'oeste'), null);

echo "\nRestricciones de recinto\n";
comprobar('Una tropa no entra en Fábrica', $m->motivoRecinto([], 'tropa', 'fabrica') !== null, true);
comprobar('Un cabo no entra en Espías', $m->motivoRecinto([], 'cabo', 'espias') !== null, true);
comprobar('Cualquier ficha entra en el Muro', $m->motivoRecinto([], 'markus', 'muro'), null);
$lleno = tablero($m, [['cabo', 'fabrica'], ['peon', 'fabrica'], ['cabo', 'fabrica']]);
comprobar('Fábrica llena (3) rechaza la cuarta', $m->motivoRecinto($lleno, 'peon', 'fabrica') !== null, true);

echo "\nDado (sección 3)\n";
comprobar('Lado izquierdo bloquea Oeste para los demás', in_array('oeste', $m->recintosPermitidosPorDado('lado_izquierdo', false), true), false);
comprobar('Lado izquierdo no bloquea al que tiró', in_array('oeste', $m->recintosPermitidosPorDado('lado_izquierdo', true), true), true);
comprobar('Lado derecho deja Muro, Oeste, Fábrica y Espías',
    $m->recintosPermitidosPorDado('lado_derecho', false), ['oeste', 'fabrica', 'espias', 'muro']);
comprobar('Solo Espías = Espías y Muro', $m->recintosPermitidosPorDado('solo_espias', false), ['espias', 'muro']);
comprobar('Solo Militares = Militares y Muro', $m->recintosPermitidosPorDado('solo_militares', false), ['militares', 'muro']);
comprobar('Bloqueo de Fábrica solo saca Fábrica', in_array('fabrica', $m->recintosPermitidosPorDado('bloquea_fabrica', false), true), false);
$noExento = new MotorJuego(ConfigPartida::desdeArreglo(['tiradorExento' => false]));
comprobar('Personalizada sin exención: al que tiró también lo bloquea',
    in_array('oeste', $noExento->recintosPermitidosPorDado('lado_izquierdo', true), true), false);

echo "\nSalida al Muro (caso que el manual no cubre)\n";
$t = tablero($m, [['cabo', 'oeste']]);
comprobar('Solo políticos en la mano + Solo Militares + Políticos sin abrir: va al Muro',
    $m->opcionesColocacion($t, ['politico'], 'solo_militares', false), ['politico' => ['muro']]);
comprobar('validarColocacion lo acepta', $m->validarColocacion($t, ['politico'], 'politico', 'muro', 'solo_militares', false), null);
comprobar('Si había otra ficha que sí entraba, el político no puede ir al Muro',
    $m->validarColocacion($t, ['politico', 'tropa'], 'politico', 'muro', 'solo_militares', false) !== null, true);
comprobar('No se puede colocar una ficha que no está en la mano',
    $m->validarColocacion([], ['cabo'], 'tropa', 'oeste', null, false) !== null, true);

echo "\nCambiar ficha (sección 3.5)\n";
comprobar('Sin fichas en el tablero no hay que mover', $m->debeMover([], 'cambiar_ficha', true, 1, 1), false);
$t = $m->colocar([], 1, 'cabo', 'oeste', 1, 1);
comprobar('Con una ficha de un turno anterior, el que tiró debe mover', $m->debeMover($t, 'cambiar_ficha', true, 1, 2), true);
comprobar('El que no tiró no mueve', $m->debeMover($t, 'cambiar_ficha', false, 1, 2), false);
comprobar('La ficha del turno actual no se puede mover', $m->debeMover($t, 'cambiar_ficha', true, 1, 1), false);
$t = tablero($m, [['peon', 'fabrica'], ['cabo', 'fabrica'], ['oficial_obrera', 'fabrica']]);
comprobar('Fábrica antes de mover = 11', $m->puntuar($t)['recintos']['fabrica'], 11);
$t = $m->mover($t, 1, 'oeste');
comprobar('Al sacar el Peón, las demás se corren a la izquierda',
    array_column(MotorJuego::fichasEn($t, 'fabrica'), 'posicion'), [1, 2]);
comprobar('Y se recalcula: Cabo 1 + Oficial Obrera 3 = 4', $m->puntuar($t)['recintos']['fabrica'], 4);
$t = tablero($m, [['politico', 'politicos'], ['politico', 'oeste']]);
comprobar('No se puede sacar el político de Políticos si hay otros afuera', $m->destinosMovimiento($t, 1), []);

echo "\nGanador y desempate (sección 6.3)\n";
comprobar('Más puntos gana', MotorJuego::clasificar([
    'a' => ['total' => 30, 'politicos' => 1], 'b' => ['total' => 25, 'politicos' => 0]]), ['a' => 1, 'b' => 2]);
comprobar('Empate: gana quien tiene menos políticos', MotorJuego::clasificar([
    'a' => ['total' => 30, 'politicos' => 3], 'b' => ['total' => 30, 'politicos' => 1]]), ['b' => 1, 'a' => 2]);
comprobar('Empate total: comparten el puesto', MotorJuego::clasificar([
    'a' => ['total' => 30, 'politicos' => 2], 'b' => ['total' => 30, 'politicos' => 2], 'c' => ['total' => 10, 'politicos' => 0]]),
    ['a' => 1, 'b' => 1, 'c' => 3]);

echo "\nReglas personalizadas\n";
$custom = new MotorJuego(ConfigPartida::desdeArreglo(['rondas' => 3, 'fichasPorRonda' => 6]));
comprobar('5 jugadores × 3 rondas × 6 = 90 fichas: no alcanza la bolsa', $custom->validarParaJugadores(5) !== null, true);
comprobar('2 jugadores × 3 rondas × 6 = 36: alcanza', $custom->validarParaJugadores(2), null);
$valores = new MotorJuego(ConfigPartida::desdeArreglo(['valorTipo1' => 2, 'valorTipo2' => 4, 'valorTipo3' => 10]));
comprobar('Valores 2/4/10 en Fábrica', $valores->puntuar(tablero($valores, [['cabo', 'fabrica'], ['peon', 'fabrica'], ['oficial_obrera', 'fabrica']]))['recintos']['fabrica'], 16);
comprobar('Config fuera de rango da error', ConfigPartida::desdeArreglo(['rondas' => 9])->validar() !== null, true);
comprobar('La config oficial se reconoce como oficial', ConfigPartida::oficial()->esOficial(), true);

echo "\nJugada automática\n";
$mano = [['nro' => 10, 'tipo' => 'markus'], ['nro' => 11, 'tipo' => 'cabo']];
$t = tablero($m, [['ursula', 'espias']]);
$jugada = $m->mejorColocacion($t, $mano, null, false, 1, 2);
comprobar('Con Ursula en Espías elige completar la pareja con Markus', [$jugada['tipo'], $jugada['recinto']], ['markus', 'espias']);
$jugada = $m->mejorColocacion([], [['nro' => 1, 'tipo' => 'cabo']], 'solo_espias', false, 1, 1);
comprobar('Si el dado no deja nada, va al Muro', $jugada['recinto'], 'muro');

echo "\nPartida completa simulada (2 jugadores, 12 turnos)\n";
$bolsa = MotorJuego::armarBolsa(2);
$tableros = [[], []];
$nro = 0;
$errores = 0;
for ($ronda = 1; $ronda <= 2; $ronda++) {
    $manos = [[], []];
    for ($j = 0; $j < 2; $j++) {
        for ($k = 0; $k < 6; $k++) {
            $manos[$j][] = ['nro' => ++$nro, 'tipo' => array_pop($bolsa)];
        }
    }
    for ($turno = 1; $turno <= 6; $turno++) {
        $cara = $m->tirarDado();
        $tirador = ($turno + $ronda) % 2;
        for ($j = 0; $j < 2; $j++) {
            $esTirador = $j === $tirador;
            if ($m->debeMover($tableros[$j], $cara, $esTirador, $ronda, $turno)) {
                $mov = $m->mejorMovimiento($tableros[$j], $ronda, $turno);
                $tableros[$j] = $m->mover($tableros[$j], $mov['nro'], $mov['destino']);
            }
            $jugada = $m->mejorColocacion($tableros[$j], $manos[$j], $cara, $esTirador, $ronda, $turno, 0.5);
            if ($jugada === null) {
                $errores++;
                array_shift($manos[$j]);
                continue;
            }
            $tableros[$j] = $m->colocar($tableros[$j], $jugada['nro'], $jugada['tipo'], $jugada['recinto'], $ronda, $turno);
            $manos[$j] = array_values(array_filter($manos[$j], fn($f) => $f['nro'] !== $jugada['nro']));
        }
        $manos = [$manos[1], $manos[0]]; // pasan a la izquierda
    }
}
comprobar('Cada jugador termina con 12 fichas', [count($tableros[0]), count($tableros[1])], [12, 12]);
comprobar('Nunca quedó una ficha sin lugar', $errores, 0);
$valido = true;
foreach ($tableros as $t) {
    foreach (array_keys(MotorJuego::RECINTOS) as $r) {
        if (count(MotorJuego::fichasEn($t, $r)) > $m->capacidad($r)) {
            $valido = false;
        }
    }
}
comprobar('Ningún recinto superó su capacidad', $valido, true);
echo '  Puntajes de la simulación: ' . $m->puntuar($tableros[0])['total'] . ' y ' . $m->puntuar($tableros[1])['total'] . "\n";

echo "\n" . ($fallados === 0 ? "TODO BIEN" : "HAY FALLAS") . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
