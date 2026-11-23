<?php
/*
 * Tests del mundo abierto (usa la base de datos real, como partida_test.php).
 *     php tests/mundo_test.php
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
SucesoLogica::$sucesoFijo = ['tipo' => null];   // sin suceso: la influencia no depende de la hora del test
$mundo = new MundoLogica();
$partidas = new PartidaLogica();
const AURA = 1;
const TESTEO = 3;

echo "\nMovimientos y colisiones (mapa Tiled, sin base)\n";
$mapaT = MundoLogica::mapaTiled();
comprobar('El mapa tiene 18 edificios (12 cafés y 6 especiales)', count($mapaT['edificios']), 18);
comprobar('Los dos puntos de aparición están libres',
    [MundoLogica::libre(...MundoLogica::puntoDeAparicion('oeste')), MundoLogica::libre(...MundoLogica::puntoDeAparicion('este'))], [true, true]);
$todasLasPuertasLibres = true;
foreach ($mapaT['edificios'] as $e) {
    $todasLasPuertasLibres = $todasLasPuertasLibres && MundoLogica::libre($e['puertaX'], $e['puertaY']);
}
comprobar('Todas las puertas de los edificios se pueden pisar', $todasLasPuertasLibres, true);
comprobar('Caminar por la calle', MundoLogica::movimientoValido(980, 700, 1000, 700, 0.3), true);
comprobar('No se cruza el Muro fuera del paso', MundoLogica::movimientoValido(1140, 820, 1220, 820, 0.5), false);
comprobar('No se puede estar adentro del Muro', MundoLogica::movimientoValido(1140, 820, 1180, 820, 0.3), false);
comprobar('Por Checkpoint Charlie sí se cruza', MundoLogica::movimientoValido(1140, 940, 1220, 940, 0.3), true);
comprobar('No se entra a un edificio por las paredes', MundoLogica::movimientoValido(700, 700, 740, 700, 0.3), false);
comprobar('Correr a través de un edificio se rechaza (se revisa todo el camino)', MundoLogica::movimientoValido(700, 700, 950, 700, 2.0), false);
comprobar('No hay teletransporte', MundoLogica::movimientoValido(980, 700, 2000, 1200, 0.3), false);
comprobar('No se sale del mapa', MundoLogica::movimientoValido(20, 20, 0, 20, 0.3), false);
comprobar('La franja de la muerte es inalcanzable desde el Este salvo por el paso', MundoLogica::movimientoValido(1420, 700, 1260, 700, 2.0), false);
comprobar('Punto (760, 800) está en Tiergarten', MundoLogica::distritoEn(760, 800, (new FachadaPersistencia())->retornoIPersistenciaMundo()->distritos()), 4);

$db->exec("UPDATE PARTIDAS SET Estado = 'cancelada' WHERE Estado IN ('esperando', 'en_curso')");
$db->exec('DELETE FROM POSICIONES_MUNDO');
$db->exec('UPDATE JUGADORES SET Bando = NULL, IDTemporadaBando = NULL');

echo "\nBandos\n";
comprobar('AURA elige el Oeste', $mundo->elegirBando(AURA, 'oeste')['exito'], true);
comprobar('No puede cambiar en la misma temporada', $mundo->elegirBando(AURA, 'este')['exito'], false);
comprobar('testeo elige el Este', $mundo->elegirBando(TESTEO, 'este')['exito'], true);
comprobar('Un bando inventado no vale', $mundo->elegirBando(TESTEO, 'luna')['exito'], false);
$estado = $mundo->estado(AURA);
comprobar('AURA aparece de su lado del Muro', $estado['yo']['x'] < 1160, true);

echo "\nMundo vivo: reloj, clima, NPCs y apariencia\n";
comprobar('El clima es determinista', MundoLogica::reloj(1794500000.0)['clima'], MundoLogica::reloj(1794500300.0)['clima']);
comprobar('Medianoche al empezar el ciclo', MundoLogica::reloj(1800000.0)['hora'], 0.0);
comprobar('Mediodía a mitad del ciclo', MundoLogica::reloj(1800900.0)['hora'], 12.0);
$cuenta = [];
for ($i = 0; $i < 5000; $i++) {
    $k = MundoLogica::climaDeTramo($i);
    $cuenta[$k] = ($cuenta[$k] ?? 0) + 1;
}
$parecido = true;
foreach (MundoLogica::CLIMAS as $clima => $porcentaje) {
    $parecido = $parecido && abs(($cuenta[$clima] ?? 0) / 50 - $porcentaje) < 4;
}
comprobar('La frecuencia de cada clima se parece a la buscada (±4 puntos)', $parecido, true);
$objetos = [];
foreach ($mapaT['json']['layers'] as $capa) {
    if ($capa['name'] === 'objetos') {
        $objetos = $capa['objects'];
    }
}
$rutas = array_values(array_filter($objetos, fn($o) => $o['type'] === 'ruta'));
comprobar('Hay rutas de peatones, patrullas y tranvías', count($rutas) >= 20 && count(array_filter($rutas, fn($r) => str_starts_with($r['name'], 'tranvia'))) === 2, true);
$dentro = true;
foreach ($rutas as $r) {
    foreach ($r['polyline'] as $pt) {
        $dentro = $dentro && $pt['x'] >= 0 && $pt['y'] >= 0 && $pt['x'] <= $mapaT['ancho'] && $pt['y'] <= $mapaT['alto'];
    }
}
comprobar('Todas las rutas quedan dentro del mapa', $dentro, true);
comprobar('Hay un guardia a cada lado de Checkpoint Charlie', count(array_filter($objetos, fn($o) => $o['type'] === 'guardia')), 2);
comprobar('Todos los edificios tienen ventanas que se iluminan de noche', count(array_filter($mapaT['edificios'], fn($e) => count($e['luces']) > 0)), 18);
comprobar('Una apariencia que no existe se rechaza', $mundo->guardarApariencia(AURA, 9, 0, 0)['exito'], false);
$ap = $mundo->guardarApariencia(AURA, 2, 3, 1);
comprobar('Piel 2, pelo 3, gorro 1 = sprite 34', [$ap['exito'], $ap['indice']], [true, 34]);
comprobar('El estado completo trae mi apariencia', $mundo->estado(AURA)['yo']['apariencia']['indice'], 34);
$yoEnLista = array_values(array_filter($mundo->estadoLiviano(TESTEO)['jugadores'], fn($j) => $j['id'] === AURA));
comprobar('Los demás me ven con la apariencia nueva', $yoEnLista[0]['ap'] ?? null, 34);
comprobar('El estado completo trae el reloj del mundo', isset($mundo->estado(AURA)['reloj']['clima']), true);

echo "\nCafés y mesas\n";
comprobar('Lejos del café no se puede armar mesa', $mundo->crearMesa(AURA, 4, 2, 'AURA')['exito'], false);
$cafe4 = array_values(array_filter($mapaT['edificios'], fn($e) => $e['tipo'] === 'cafe' && $e['distrito'] === 4))[0];
// Lo pongo junto a la puerta del café de Tiergarten (como si hubiera caminado hasta ahí).
$db->exec('UPDATE POSICIONES_MUNDO SET X = ' . $cafe4['puertaX'] . ', Y = ' . ($cafe4['puertaY'] + 40) . ' WHERE IDUsuario = ' . AURA);
$db->exec("UPDATE POSICIONES_MUNDO SET UltimaActividad = NOW(3) - INTERVAL 2 SECOND WHERE IDUsuario = " . AURA);
$r = $mundo->mover(AURA, $cafe4['puertaX'] + 20, $cafe4['puertaY'] + 40, 2);
comprobar('Un paso posible se acepta', $r['corregido'], false);
$db->exec("UPDATE POSICIONES_MUNDO SET UltimaActividad = NOW(3) - INTERVAL 2 SECOND WHERE IDUsuario = " . AURA);
$r = $mundo->mover(AURA, 2000, 300, 1);
comprobar('Un salto imposible se rechaza y lo deja donde estaba', [$r['corregido'], $r['x'], $r['y']], [true, $cafe4['puertaX'] + 20, $cafe4['puertaY'] + 40]);
$mesa = $mundo->crearMesa(AURA, 4, 2, 'AURA');
comprobar('Al lado del café se arma la mesa', $mesa['exito'], true);
$sala = $mundo->salas(4);
comprobar('La mesa aparece en el café de Tiergarten', count($sala) === 1 && $sala[0]['id'] === $mesa['idPartida'], true);
comprobar('…y no en otro café', $mundo->salas(9), []);

echo "\nInfluencia al terminar la partida\n";
$id = $mesa['idPartida'];
$partidas->unirse(TESTEO, $id, '');
for ($i = 0; $i < 60; $i++) {
    $db->exec("UPDATE PARTIDAS SET InicioFase = InicioFase - INTERVAL 70 SECOND WHERE IDPartida = $id");
    $v = $partidas->vista($id, AURA);
    $partidas->vista($id, TESTEO);
    if ($v['estado'] === 'finalizada') {
        break;
    }
}
comprobar('La partida del café termina (jugada automática)', $v['estado'], 'finalizada');
$temporada = $mundo->temporadaVigente();
$dominio = $mundo->dominio((int) $temporada['IDTemporada']);
$esperadoOeste = 0;
$esperadoEste = 0;
foreach ($v['jugadores'] as $j) {
    $puntos = $j['resultado']['posicion'] === 1 ? 3 : 2;
    if ($j['id'] === AURA) $esperadoOeste = $puntos;
    if ($j['id'] === TESTEO) $esperadoEste = $puntos;
}
comprobar('El Oeste suma en Tiergarten (3 si ganó, 2 si salió segundo)', $dominio[4]['oeste'], $esperadoOeste);
comprobar('El Este también suma en Tiergarten', $dominio[4]['este'], $esperadoEste);
comprobar('Nadie suma en otros distritos', $dominio[9]['oeste'] + $dominio[9]['este'], 0);

echo "\nTemporadas\n";
$numero = (int) $temporada['Numero'];
$db->exec('UPDATE TEMPORADAS SET FechaFin = NOW() - INTERVAL 1 SECOND WHERE IDTemporada = ' . (int) $temporada['IDTemporada']);
$nueva = $mundo->temporadaVigente();
comprobar('Al vencer, arranca la temporada siguiente', (int) $nueva['Numero'], $numero + 1);
$cerrada = $db->query('SELECT Cerrada, BandoGanador FROM TEMPORADAS WHERE Numero = ' . $numero)->fetch(PDO::FETCH_ASSOC);
$ganadorEsperado = $esperadoOeste === $esperadoEste ? 'empate' : ($esperadoOeste > $esperadoEste ? 'oeste' : 'este');
comprobar('La anterior queda cerrada con su ganador', [(int) $cerrada['Cerrada'], $cerrada['BandoGanador']], [1, $ganadorEsperado]);
comprobar('En la temporada nueva se puede cambiar de bando', $mundo->elegirBando(AURA, 'este')['exito'], true);
comprobar('La influencia arranca de cero', array_sum(array_column($mundo->dominio((int) $nueva['IDTemporada']), 'oeste')), 0);

$vacia = $mundo->temporadaVigente();
$db->exec('UPDATE TEMPORADAS SET FechaFin = NOW() - INTERVAL 1 SECOND WHERE IDTemporada = ' . (int) $vacia['IDTemporada']);
$mundo->temporadaVigente();
comprobar('Una temporada sin influencia de nadie cierra empatada (sin error)', $db->query('SELECT BandoGanador FROM TEMPORADAS WHERE IDTemporada = ' . (int) $vacia['IDTemporada'])->fetchColumn(), 'empate');

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
