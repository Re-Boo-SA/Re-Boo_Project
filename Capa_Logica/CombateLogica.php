<?php
/*
 * ======================================================================
 *  COMBATE TÁCTICO POR TURNOS (campaña, etapa D)
 *
 *  Todo lo resuelve el servidor: el navegador solo pide acciones y anima los eventos.
 *  - Grilla del mapa de la escena (mosaicos de 40 px). Cada unidad: vida, movimiento, puntería.
 *  - En su turno, cada personaje puede MOVERSE (hasta su alcance, sin atravesar obstáculos
 *    ni a otros) y hacer UNA acción: DISPARAR (con línea de vista) o CUBRIRSE.
 *  - Probabilidad de acierto: puntería, menos 5 por casilla de distancia después de la 3.ª,
 *    menos 30 si el blanco tiene cobertura (un obstáculo pegado a él, del lado del tirador),
 *    menos 15 si se cubrió. Entre 5 y 90.
 *  - Las tiradas salen de una semilla que nunca se manda al navegador.
 *  - Objetivo: 'resistir' N rondas (o derribar a todos), o 'eliminar' a todos antes de N rondas (si no, se pierde).
 *  - Los rivales los mueve una IA simple: buscan una casilla desde donde tengan buen tiro y
 *    poco riesgo; si no tienen tiro, se cubren.
 * ======================================================================
 */
class CombateLogica
{
    public const T = 40;

    /* ---------- mapa ---------- */

    /** Grilla de obstáculos del mapa de una escena: ['w', 'h', 'solido' => [f][c] bool]. */
    public static function grilla(array $mapa): array
    {
        $col = array_values(array_filter($mapa['layers'], fn($l) => $l['name'] === 'colision'))[0]['data'];
        $solido = [];
        for ($f = 0; $f < $mapa['height']; $f++) {
            for ($c = 0; $c < $mapa['width']; $c++) {
                $solido[$f][$c] = (bool) $col[$f * $mapa['width'] + $c];
            }
        }
        return ['w' => $mapa['width'], 'h' => $mapa['height'], 'solido' => $solido];
    }

    private static function solida(array $e, int $c, int $f): bool
    {
        return $c < 0 || $f < 0 || $c >= $e['grilla']['w'] || $f >= $e['grilla']['h'] || $e['grilla']['solido'][$f][$c];
    }

    private static function ocupada(array $e, int $c, int $f, ?string $salvo = null): bool
    {
        foreach ($e['unidades'] as $id => $u) {
            if ($id !== $salvo && !$u['caido'] && $u['x'] === $c && $u['y'] === $f) {
                return true;
            }
        }
        return false;
    }

    /* ---------- estado ---------- */

    public static function nuevo(array $def, array $mapa, int $semilla): array
    {
        $unidades = [];
        foreach ($def['unidades'] as $id => $u) {
            $unidades[$id] = ['lado' => $u['lado'], 'x' => $u['celda'][0], 'y' => $u['celda'][1], 'vida' => $u['vida'], 'vidaMax' => $u['vida'],
                'mov' => $u['mov'], 'punteria' => $u['punteria'], 'movio' => false, 'actuo' => false, 'cubierto' => false, 'caido' => false];
        }
        return ['id' => $def['id'], 'ronda' => 1, 'turno' => 'aliado', 'unidades' => $unidades, 'lider' => $def['lider'],
            'objetivo' => $def['objetivo'], 'grilla' => self::grilla($mapa), 'semilla' => $semilla, 'tiradas' => 0, 'resultado' => null, 'eventos' => []];
    }

    /** Lo que ve el navegador: sin semilla y con lo que puede hacer cada personaje (casillas y probabilidades). */
    public static function vista(array $e): array
    {
        $acciones = [];
        if ($e['resultado'] === null && $e['turno'] === 'aliado') {
            foreach ($e['unidades'] as $id => $u) {
                if ($u['lado'] !== 'aliado' || $u['caido']) continue;
                $tiros = [];
                foreach ($e['unidades'] as $oid => $o) {
                    if ($o['lado'] === 'enemigo' && !$o['caido']) $tiros[$oid] = self::probabilidad($e, $id, $oid);
                }
                $acciones[$id] = ['alcanzables' => ($u['movio'] || $u['actuo']) ? [] : self::alcanzables($e, $id), 'tiros' => $u['actuo'] ? (object) [] : (object) $tiros];
            }
        }
        return ['id' => $e['id'], 'ronda' => $e['ronda'], 'turno' => $e['turno'], 'resultado' => $e['resultado'], 'objetivo' => $e['objetivo'], 'lider' => $e['lider'],
            'unidades' => $e['unidades'], 'acciones' => (object) $acciones, 'eventos' => $e['eventos']];
    }

    /* ---------- reglas ---------- */

    /** Casillas a las que puede llegar caminando (búsqueda en anchura, 4 direcciones). [[c, f], ...] sin la propia. */
    public static function alcanzables(array $e, string $id): array
    {
        $u = $e['unidades'][$id];
        $dist = [$u['y'] * 1000 + $u['x'] => 0];
        $cola = [[$u['x'], $u['y']]];
        $r = [];
        while ($cola) {
            [$c, $f] = array_shift($cola);
            $d = $dist[$f * 1000 + $c];
            if ($d >= $u['mov']) continue;
            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dc, $df]) {
                [$nc, $nf] = [$c + $dc, $f + $df];
                if (isset($dist[$nf * 1000 + $nc]) || self::solida($e, $nc, $nf) || self::ocupada($e, $nc, $nf, $id)) continue;
                $dist[$nf * 1000 + $nc] = $d + 1;
                $cola[] = [$nc, $nf];
                $r[] = [$nc, $nf];
            }
        }
        return $r;
    }

    /** Línea de vista entre dos casillas: ningún obstáculo en el medio (los personajes no tapan). */
    public static function lineaDeVista(array $e, int $c0, int $f0, int $c1, int $f1): bool
    {
        $x0 = $c0 * self::T + 20; $y0 = $f0 * self::T + 20; $x1 = $c1 * self::T + 20; $y1 = $f1 * self::T + 20;
        $pasos = max(1, (int) ceil(hypot($x1 - $x0, $y1 - $y0) / 8));
        for ($i = 1; $i < $pasos; $i++) {
            $c = intdiv((int) ($x0 + ($x1 - $x0) * $i / $pasos), self::T);
            $f = intdiv((int) ($y0 + ($y1 - $y0) * $i / $pasos), self::T);
            if (($c !== $c0 || $f !== $f0) && ($c !== $c1 || $f !== $f1) && self::solida($e, $c, $f)) return false;
        }
        return true;
    }

    /** ¿El blanco tiene un obstáculo pegado, del lado del tirador? */
    public static function cobertura(array $e, int $bc, int $bf, int $tc, int $tf): bool
    {
        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dc, $df]) {
            if (self::solida($e, $bc + $dc, $bf + $df) && ($dc * ($tc - $bc) + $df * ($tf - $bf)) > 0) return true;
        }
        return false;
    }

    /** Probabilidad (0 a 90) de que $tirador le acierte a $blanco desde donde está (o desde $desde). */
    public static function probabilidad(array $e, string $tirador, string $blanco, ?array $desde = null, ?array $blancoEn = null): int
    {
        $t = $e['unidades'][$tirador]; $b = $e['unidades'][$blanco];
        [$tc, $tf] = $desde ?? [$t['x'], $t['y']];
        [$bc, $bf] = $blancoEn ?? [$b['x'], $b['y']];
        if (!self::lineaDeVista($e, $tc, $tf, $bc, $bf)) return 0;
        $dist = hypot($bc - $tc, $bf - $tf);
        $p = $t['punteria'] - max(0, $dist - 3) * 5 - (self::cobertura($e, $bc, $bf, $tc, $tf) ? 30 : 0) - ($b['cubierto'] ? 15 : 0);
        return (int) max(5, min(90, round($p)));
    }

    private static function tirada(array &$e): int
    {
        return crc32($e['semilla'] . ':' . ($e['tiradas']++)) % 100;
    }

    private static function revisarFin(array &$e): void
    {
        if ($e['unidades'][$e['lider']]['caido']) {
            $e['resultado'] = 'derrota';
        } elseif (!array_filter($e['unidades'], fn($u) => $u['lado'] === 'enemigo' && !$u['caido'])) {
            $e['resultado'] = 'victoria';
        }
        if ($e['resultado'] !== null) $e['eventos'][] = ['tipo' => 'fin', 'resultado' => $e['resultado']];
    }

    private static function puedeActuar(array $e, string $id, string $lado): ?string
    {
        $u = $e['unidades'][$id] ?? null;
        if ($e['resultado'] !== null) return 'El combate ya terminó.';
        if ($u === null || $u['lado'] !== $lado || $e['turno'] !== $lado) return 'Ese personaje no puede actuar ahora.';
        if ($u['caido']) return 'Ese personaje está fuera de combate.';
        return null;
    }

    public static function mover(array &$e, string $id, int $c, int $f, string $lado = 'aliado'): ?string
    {
        if (($error = self::puedeActuar($e, $id, $lado)) !== null) return $error;
        $u = $e['unidades'][$id];
        if ($u['movio'] || $u['actuo']) return 'Ese personaje ya se movió o actuó en este turno.';
        if (!in_array([$c, $f], self::alcanzables($e, $id), true)) return 'No puede llegar hasta ahí.';
        $e['eventos'][] = ['tipo' => 'mover', 'unidad' => $id, 'desde' => [$u['x'], $u['y']], 'a' => [$c, $f]];
        $e['unidades'][$id]['x'] = $c; $e['unidades'][$id]['y'] = $f; $e['unidades'][$id]['movio'] = true;
        return null;
    }

    public static function disparar(array &$e, string $id, string $blanco, string $lado = 'aliado'): ?string
    {
        if (($error = self::puedeActuar($e, $id, $lado)) !== null) return $error;
        $b = $e['unidades'][$blanco] ?? null;
        if ($e['unidades'][$id]['actuo']) return 'Ese personaje ya actuó en este turno.';
        if ($b === null || $b['lado'] === $lado || $b['caido']) return 'Ese no es un blanco válido.';
        $p = self::probabilidad($e, $id, $blanco);
        if ($p === 0) return 'No tiene línea de vista.';
        $acierto = self::tirada($e) < $p;
        if ($acierto) {
            $e['unidades'][$blanco]['vida']--;
            if ($e['unidades'][$blanco]['vida'] <= 0) $e['unidades'][$blanco]['caido'] = true;
        }
        $e['unidades'][$id]['actuo'] = true;
        $e['eventos'][] = ['tipo' => 'disparo', 'unidad' => $id, 'blanco' => $blanco, 'probabilidad' => $p, 'acierto' => $acierto,
            'vida' => $e['unidades'][$blanco]['vida'], 'caido' => $e['unidades'][$blanco]['caido']];
        self::revisarFin($e);
        return null;
    }

    public static function cubrirse(array &$e, string $id, string $lado = 'aliado'): ?string
    {
        if (($error = self::puedeActuar($e, $id, $lado)) !== null) return $error;
        if ($e['unidades'][$id]['actuo']) return 'Ese personaje ya actuó en este turno.';
        $e['unidades'][$id]['cubierto'] = true;
        $e['unidades'][$id]['actuo'] = true;
        $e['eventos'][] = ['tipo' => 'cubrirse', 'unidad' => $id];
        return null;
    }

    /* ---------- inteligencia de los rivales (y del modo automático de pruebas) ---------- */

    /** Mueve y actúa con una unidad: elige la casilla con mejor tiro y menos riesgo; si no hay buen tiro, se cubre. */
    public static function jugarUnidad(array &$e, string $id): void
    {
        $u = $e['unidades'][$id];
        $lado = $u['lado'];
        $rivales = array_keys(array_filter($e['unidades'], fn($o) => $o['lado'] !== $lado && !$o['caido']));
        if (!$rivales) return;
        $mejorTiro = function (array $desde) use ($e, $id, $rivales) {
            $m = 0;
            foreach ($rivales as $r) $m = max($m, self::probabilidad($e, $id, $r, $desde));
            return $m;
        };
        $riesgo = function (array $en) use ($e, $id, $rivales) {
            $m = 0;
            foreach ($rivales as $r) $m = max($m, self::probabilidad($e, $r, $id, null, $en));
            return $m;
        };
        // Sin buen tiro, avanzan hacia el rival más cercano (si no, alcanzaría con esperar a que pasen las rondas).
        $cerca = function (array $en) use ($e, $rivales) {
            $m = PHP_INT_MAX;
            foreach ($rivales as $r) $m = min($m, abs($e['unidades'][$r]['x'] - $en[0]) + abs($e['unidades'][$r]['y'] - $en[1]));
            return $m;
        };
        if (!$u['movio']) {
            $mejor = [$u['x'], $u['y']];
            $puntaje = $mejorTiro($mejor) - 0.6 * $riesgo($mejor) - 1.2 * $cerca($mejor);
            foreach (self::alcanzables($e, $id) as $celda) {
                $p = $mejorTiro($celda) - 0.6 * $riesgo($celda) - 1.2 * $cerca($celda) - 0.5 * (abs($celda[0] - $u['x']) + abs($celda[1] - $u['y']));
                if ($p > $puntaje + 0.01) { $puntaje = $p; $mejor = $celda; }
            }
            if ($mejor !== [$u['x'], $u['y']]) self::mover($e, $id, $mejor[0], $mejor[1], $lado);
        }
        $blanco = null; $p = 0;
        foreach ($rivales as $r) {
            $q = self::probabilidad($e, $id, $r);
            if ($q > $p) { $p = $q; $blanco = $r; }
        }
        if ($blanco !== null && $p >= 25) self::disparar($e, $id, $blanco, $lado);
        else self::cubrirse($e, $id, $lado);
    }

    /** Termina el turno de los aliados: juegan los rivales y empieza la ronda siguiente. */
    public static function terminarTurno(array &$e): ?string
    {
        if ($e['resultado'] !== null) return 'El combate ya terminó.';
        if ($e['turno'] !== 'aliado') return 'No es tu turno.';
        $e['turno'] = 'enemigo';
        foreach ($e['unidades'] as $id => $u) {
            if ($u['lado'] === 'enemigo') { $e['unidades'][$id]['movio'] = false; $e['unidades'][$id]['actuo'] = false; $e['unidades'][$id]['cubierto'] = false; }
        }
        $e['eventos'][] = ['tipo' => 'turno', 'lado' => 'enemigo', 'ronda' => $e['ronda']];
        foreach (array_keys($e['unidades']) as $id) {
            if ($e['resultado'] !== null) break;
            if ($e['unidades'][$id]['lado'] === 'enemigo' && !$e['unidades'][$id]['caido']) self::jugarUnidad($e, $id);
        }
        if ($e['resultado'] === null) {
            $e['ronda']++;
            if (($e['objetivo']['tipo'] ?? '') === 'resistir' && $e['ronda'] > $e['objetivo']['rondas']) {
                $e['resultado'] = 'victoria';
                $e['eventos'][] = ['tipo' => 'fin', 'resultado' => 'victoria'];
            } elseif (($e['objetivo']['tipo'] ?? '') === 'eliminar' && isset($e['objetivo']['rondas']) && $e['ronda'] > $e['objetivo']['rondas']) {
                $e['resultado'] = 'derrota';   // se acabó el tiempo: llegó el refuerzo
                $e['eventos'][] = ['tipo' => 'fin', 'resultado' => 'derrota'];
            } else {
                $e['turno'] = 'aliado';
                foreach ($e['unidades'] as $id => $u) {
                    if ($u['lado'] === 'aliado') { $e['unidades'][$id]['movio'] = false; $e['unidades'][$id]['actuo'] = false; $e['unidades'][$id]['cubierto'] = false; }
                }
                $e['eventos'][] = ['tipo' => 'turno', 'lado' => 'aliado', 'ronda' => $e['ronda']];
            }
        }
        return null;
    }
}
?>
