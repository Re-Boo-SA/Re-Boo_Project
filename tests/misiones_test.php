<?php
/*
 * Tests de misiones, experiencia y logros (Fase 2). Usa la base de datos real.
 *     php tests/misiones_test.php
 * Mueve a AURA por el mapa escribiendo su posición (como si hubiera caminado).
 * Necesita una base RECIÉN IMPORTADA (el usuario de la app no puede borrar progreso).
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
MundoLogica::$horaFija = 12.0;
SucesoLogica::$sucesoFijo = ['tipo' => null];   // sin suceso   // los contactos tienen rutina: a las 12 están todos trabajando
$m = new MisionLogica();
$mundo = new MundoLogica();
const AURA = 1;
$datos = MisionLogica::datos();

function irA(int $x, int $y): void
{
    global $db;
    $db->exec('UPDATE POSICIONES_MUNDO SET X = ' . $x . ', Y = ' . $y . ' WHERE IDUsuario = ' . AURA);
}
function irAContacto(string $id): void
{
    $c = MisionLogica::datos()['porContacto'][$id];
    irA($c['x'] + 20, $c['y']);
}
/** Inserta una partida terminada ahora, con AURA en $posicion (null = abandonó), opcionalmente en un distrito. */
function partidaTerminada(?int $posicion, ?int $distrito = null): void
{
    global $db;
    $db->exec("INSERT INTO PARTIDAS (Nombre, Modo, Estado, MaxJugadores, FechaInicio, FechaFin) VALUES ('Test', 'normal', 'finalizada', 2, NOW(), NOW() + INTERVAL 1 SECOND)");
    $id = (int) $db->lastInsertId();
    $db->exec("INSERT INTO PARTICIPACIONES (IDPartida, IDUsuario, Asiento, Estado, Posicion) VALUES ($id, " . AURA . ", 0, '" . ($posicion === null ? 'abandono' : 'activo') . "', " . ($posicion ?? 'NULL') . ')');
    if ($distrito !== null) {
        $db->exec("INSERT INTO PARTIDA_DISTRITO (IDPartida, IDDistrito) VALUES ($id, $distrito)");
    }
}
function xp(): int
{
    global $db;
    return (int) $db->query('SELECT XP FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn();
}
function saldo(): int
{
    global $db;
    return (int) $db->query('SELECT PntsPartida FROM JUGADORES WHERE IDUsuario = ' . AURA)->fetchColumn();
}

echo "\nContenido (datos/mundo/misiones.json)\n";
$errores = [];
foreach ($datos['misiones'] as $mi) {
    if (!isset($datos['porContacto'][$mi['contacto']])) $errores[] = $mi['id'] . ': contacto inexistente';
    foreach ($mi['requisitos']['misiones'] ?? [] as $r) {
        if (!isset($datos['porMision'][$r])) $errores[] = $mi['id'] . ': requisito inexistente ' . $r;
    }
    foreach ($mi['pasos'] as $p) {
        if (!in_array($p['tipo'], ['ir', 'hablar', 'partida', 'ganar', 'desafio'], true)) $errores[] = $mi['id'] . ': tipo ' . $p['tipo'];
        if (in_array($p['tipo'], ['hablar', 'desafio'], true) && !isset($datos['porContacto'][$p['contacto']])) $errores[] = $mi['id'] . ': con quién';
        if ($p['tipo'] === 'ir') {
            $alcanzable = false;
            for ($a = 0; $a < 360 && !$alcanzable; $a += 15) {
                for ($r = 0; $r <= $p['radio'] - 5 && !$alcanzable; $r += 10) {
                    $alcanzable = MundoLogica::libre((int) ($p['x'] + cos(deg2rad($a)) * $r), (int) ($p['y'] + sin(deg2rad($a)) * $r));
                }
            }
            if (!$alcanzable) $errores[] = $mi['id'] . ': no se puede pisar ' . $p['lugar'];
        }
    }
}
comprobar('Todas las misiones apuntan a contactos, requisitos y lugares válidos', $errores, []);
comprobar('Todos los contactos están parados en lugares libres', array_values(array_filter($datos['contactos'], fn($c) => !MundoLogica::libre($c['x'], $c['y']))), []);
comprobar('Nivel: 0 XP = 1, 100 = 2, 300 = 3, 1000 = 5', [MisionLogica::nivel(0), MisionLogica::nivel(100), MisionLogica::nivel(300), MisionLogica::nivel(1000)], [1, 2, 3, 5]);

// Preparación: AURA en el mapa del lado Oeste
// El usuario de la app no puede BORRAR progreso (mínimo privilegio): el test necesita una base recién importada.
if ((int) $db->query('SELECT COUNT(*) FROM MISIONES_JUGADOR WHERE IDUsuario = ' . AURA)->fetchColumn() > 0) {
    fwrite(STDERR, "AURA ya tiene misiones: importá de nuevo la base (sql/Proyecto 2026, BDDv8.sql) y volvé a correr este test.\n");
    exit(2);
}
$mundo->elegirBando(AURA, 'oeste');
$mundo->mover(AURA, 0, 0, 2);

echo "\nHablar y aceptar (validado en el servidor)\n";
comprobar('Lejos de Ingrid no se puede hablar', $m->hablar(AURA, 'periodista')['exito'], false);
irAContacto('periodista');
$r = $m->hablar(AURA, 'periodista');
comprobar('Cerca de Ingrid, ofrece "Primeras impresiones"', $r['oferta']['id'] ?? null, 'periodista_1');
comprobar('Una misión con requisitos todavía no se ofrece', $m->aceptar(AURA, 'periodista_2')['exito'], false);
irA(980, 700);
comprobar('No se puede aceptar estando lejos', $m->aceptar(AURA, 'periodista_1')['exito'], false);
irAContacto('periodista');
comprobar('Cerca, se acepta', $m->aceptar(AURA, 'periodista_1')['exito'], true);
comprobar('No se acepta dos veces', $m->aceptar(AURA, 'periodista_1')['exito'], false);
$e = $m->estado(AURA);
comprobar('Queda activa en el paso 1 (ir a Checkpoint Charlie)', [$e['activas'][0]['id'], $e['activas'][0]['paso'], $e['activas'][0]['objetivo']['lugar']], ['periodista_1', 1, 'Checkpoint Charlie']);

echo "\nPasos que comprueba el servidor\n";
$e = $m->estado(AURA);
comprobar('Estando lejos, el paso "ir" no se cumple', $e['activas'][0]['paso'], 1);
irA(1100, 940);
$e = $m->estado(AURA);
comprobar('Al llegar a la zona, se cumple solo', [$e['activas'][0]['paso'], in_array('Objetivo cumplido: Primeras impresiones', $e['avisos'], true)], [2, true]);
$marca = array_values(array_filter($e['contactos'], fn($c) => $c['id'] === 'periodista'))[0]['marca'];
comprobar('Ingrid muestra "?" (hay que volver a hablarle)', $marca, '?');
$antesXp = xp();
$antesSaldo = saldo();
irAContacto('periodista');
$r = $m->hablar(AURA, 'periodista');
comprobar('Al volver, se completa', in_array('Misión completada: Primeras impresiones (+60 XP, +15 puntos de tienda)', $r['avisos'], true), true);
comprobar('Suma 60 XP y 15 puntos de tienda', [xp() - $antesXp, saldo() - $antesSaldo], [60, 15]);
comprobar('Da el logro "Primeros pasos"', in_array('Logro: Primeros pasos', $r['avisos'], true), true);
$r = $m->hablar(AURA, 'periodista');
comprobar('Hablar otra vez no vuelve a pagar', [xp() - $antesXp, $r['oferta']['id'] ?? null], [60, 'periodista_2']);

echo "\nMisiones de partidas\n";
$m->aceptar(AURA, 'periodista_2');
partidaTerminada(null);
comprobar('Una partida abandonada no cuenta', $m->estado(AURA)['activas'][0]['paso'], 1);
partidaTerminada(2);
comprobar('Una partida terminada (aunque se pierda) sí cuenta', $m->estado(AURA)['activas'][0]['paso'], 2);
$r = $m->hablar(AURA, 'periodista');
comprobar('160 XP = nivel 2', MisionLogica::nivel(xp()), 2);
irAContacto('obrero');
$m->aceptar(AURA, 'obrero_1');
irA(1740, 860);
$m->estado(AURA);
irAContacto('obrero');
$m->hablar(AURA, 'obrero');
$m->aceptar(AURA, 'obrero_2');
partidaTerminada(2);
comprobar('Para "ganar", perder no alcanza', array_values(array_filter($m->estado(AURA)['activas'], fn($a) => $a['id'] === 'obrero_2'))[0]['paso'], 1);
partidaTerminada(1);
comprobar('Ganar sí', array_values(array_filter($m->estado(AURA)['activas'], fn($a) => $a['id'] === 'obrero_2'))[0]['paso'], 2);
$r = $m->hablar(AURA, 'obrero');
comprobar('Al pasar 300 XP llega al nivel 3 y al logro', [MisionLogica::nivel(xp()) >= 3, in_array('Logro: Vecino de toda la vida', $r['avisos'], true)], [true, true]);

echo "\nCafés de un lado del Muro\n";
irAContacto('funcionaria');
$m->aceptar(AURA, 'funcionaria_1');
irAContacto('obrero');
$m->hablar(AURA, 'obrero');
irAContacto('funcionaria');
$m->hablar(AURA, 'funcionaria');
comprobar('Con funcionaria_1 hecha y nivel 2+, ofrece "Firma y sello"', $m->hablar(AURA, 'funcionaria')['oferta']['id'] ?? null, 'funcionaria_2');
$m->aceptar(AURA, 'funcionaria_2');
partidaTerminada(1, 4);   // Tiergarten: Oeste
partidaTerminada(1, 4);
comprobar('Partidas en cafés del Oeste no cuentan para "del Este"', array_values(array_filter($m->estado(AURA)['activas'], fn($a) => $a['id'] === 'funcionaria_2'))[0]['paso'], 1);
partidaTerminada(2, 9);   // Mitte: Este
partidaTerminada(3, 11);  // Friedrichshain: Este
comprobar('Dos en cafés del Este, sí', array_values(array_filter($m->estado(AURA)['activas'], fn($a) => $a['id'] === 'funcionaria_2'))[0]['paso'], 2);

echo "\nLos demás ven tu nivel\n";
$mundo->estado(AURA);   // refresca la copia del mapa
$yo = array_values(array_filter($mundo->estadoLiviano(3)['jugadores'], fn($j) => $j['id'] === AURA));
comprobar('El nivel aparece en la lista de jugadores del mapa', ($yo[0]['nivel'] ?? 0) === MisionLogica::nivel(xp()), true);

echo "\n" . ($fallados === 0 ? 'TODO BIEN' : 'HAY FALLAS') . ": $aprobados aprobados, $fallados fallados\n";
exit($fallados === 0 ? 0 : 1);
