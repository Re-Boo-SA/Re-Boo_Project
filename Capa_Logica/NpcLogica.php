<?php
require_once(__DIR__ . '/MundoLogica.php');
require_once(__DIR__ . '/MisionLogica.php');
require_once(__DIR__ . '/SucesoLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * ======================================================================
 *  CEREBRO DE LOS NPCs (Berlín vivo, etapa A)
 *
 *  - Rutina: dónde está cada contacto según la hora del juego. Camina entre lugares
 *    por rutas calculadas con A* sobre la grilla de colisiones (4 direcciones: no
 *    corta esquinas). Es determinista: el navegador recibe los mismos tramos y calcula
 *    la misma posición, así que todos lo ven en el mismo lugar.
 *  - Ánimo (-3 a 3): base del personaje + clima + noche. Cambia el tono y la cara.
 *  - Confianza con cada jugador (-100 a 100): arranca en +10 si sos de su lado,
 *    -10 si sos del otro, 0 sin bando. Sube y baja con misiones y respuestas.
 *  - Memoria: lo que el personaje recuerda de vos (RECUERDOS_NPC).
 *  - Diálogos: árboles en datos/mundo/dialogos.json, validados acá. Cada efecto de una
 *    opción se aplica una sola vez (no se puede "farmear" confianza repitiendo).
 * ======================================================================
 */
class NpcLogica
{
    public const VELOCIDAD = 60;   // px por segundo real al caminar
    public const ETIQUETAS = [-3 => 'malhumorado', -2 => 'malhumorado', -1 => 'serio', 0 => 'neutral', 1 => 'contento', 2 => 'animado', 3 => 'animado'];
    private const EMOCION_POR_ANIMO = ['malhumorado' => 'enojado', 'serio' => 'neutral', 'neutral' => 'neutral', 'contento' => 'contento', 'animado' => 'contento'];

    private $npc;

    public function __construct()
    {
        $this->npc = (new FachadaPersistencia())->retornoIPersistenciaNpc();
    }

    private static ?array $dialogos = null;
    private static array $rutinas = [];

    public static function dialogos(): array
    {
        if (self::$dialogos === null) {
            $j = json_decode((string) @file_get_contents(dirname(__DIR__) . '/datos/mundo/dialogos.json'), true);
            if (!is_array($j)) {
                throw new RuntimeException('Falta o está mal escrito datos/mundo/dialogos.json.');
            }
            self::$dialogos = $j;
        }
        return self::$dialogos;
    }

    private static function contacto(string $id): ?array
    {
        return MisionLogica::datos()['porContacto'][$id] ?? null;
    }

    /* ---------- caminos ---------- */

    /** A* en la grilla de mosaicos (4 vecinos). Devuelve puntos [x, y] desde $a hasta $b (los extremos exactos). */
    public static function camino(array $a, array $b): array
    {
        $m = MundoLogica::mapaTiled();
        $T = $m['tile'];
        $cols = $m['cols'];
        $filas = $m['filas'];
        $libre = fn(int $c, int $f) => $c >= 0 && $f >= 0 && $c < $cols && $f < $filas && $m['colision'][$f * $cols + $c] === 0;
        $ini = [intdiv($a[0], $T), intdiv($a[1], $T)];
        $fin = [intdiv($b[0], $T), intdiv($b[1], $T)];
        $clave = fn(array $p) => $p[1] * $cols + $p[0];
        $h = fn(array $p) => abs($p[0] - $fin[0]) + abs($p[1] - $fin[1]);
        $abiertos = new SplPriorityQueue();
        $abiertos->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        $abiertos->insert($ini, 0);
        $g = [$clave($ini) => 0];
        $de = [];
        $n = 0;
        while (!$abiertos->isEmpty()) {
            $p = $abiertos->extract();
            if ($p === $fin) {
                break;
            }
            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dc, $df]) {
                $q = [$p[0] + $dc, $p[1] + $df];
                if (!$libre($q[0], $q[1])) {
                    continue;
                }
                $costo = $g[$clave($p)] + 1;
                if (!isset($g[$clave($q)]) || $costo < $g[$clave($q)]) {
                    $g[$clave($q)] = $costo;
                    $de[$clave($q)] = $p;
                    // desempate estable (n creciente): el mismo camino siempre
                    $abiertos->insert($q, [-($costo + $h($q)), -(++$n)]);
                }
            }
        }
        if (!isset($g[$clave($fin)])) {
            return [$a, $b];   // sin camino (no debería pasar: lo controla el test)
        }
        $celdas = [$fin];
        while ($celdas[0] !== $ini) {
            array_unshift($celdas, $de[$clave($celdas[0])]);
        }
        $puntos = [$a];
        foreach (array_slice($celdas, 1, -1) as $c) {
            $puntos[] = [$c[0] * $T + intdiv($T, 2), $c[1] * $T + intdiv($T, 2)];
        }
        $puntos[] = $b;
        // Saca los puntos intermedios alineados (menos datos, mismo recorrido)
        $simple = [$puntos[0]];
        for ($i = 1; $i < count($puntos) - 1; $i++) {
            [$p0, $p1, $p2] = [end($simple), $puntos[$i], $puntos[$i + 1]];
            if (($p1[0] - $p0[0]) * ($p2[1] - $p1[1]) !== ($p1[1] - $p0[1]) * ($p2[0] - $p1[0])) {
                $simple[] = $p1;
            }
        }
        $simple[] = end($puntos);
        return $simple;
    }

    private static function largo(array $puntos): float
    {
        $l = 0.0;
        for ($i = 1; $i < count($puntos); $i++) {
            $l += hypot($puntos[$i][0] - $puntos[$i - 1][0], $puntos[$i][1] - $puntos[$i - 1][1]);
        }
        return $l;
    }

    /* ---------- rutina ---------- */

    /**
     * Tramos del día de un contacto: [{desde, dur (horas de juego), tipo: caminar|quieto|ausente, ...}].
     * Al llegar la hora de un punto de la rutina, sale caminando desde el lugar anterior.
     */
    public static function rutina(string $idContacto): array
    {
        if (isset(self::$rutinas[$idContacto])) {
            return self::$rutinas[$idContacto];
        }
        $c = self::contacto($idContacto);
        $puntos = $c['rutina'] ?? [['hora' => 0, 'x' => $c['x'], 'y' => $c['y'], 'dir' => $c['dir'], 'actividad' => '']];
        usort($puntos, fn($a, $b) => $a['hora'] <=> $b['hora']);
        $segPorHora = MundoLogica::SEGUNDOS_POR_DIA / 24;
        $tramos = [];
        $n = count($puntos);
        for ($i = 0; $i < $n; $i++) {
            $antes = $puntos[($i - 1 + $n) % $n];
            $p = $puntos[$i];
            $sig = $puntos[($i + 1) % $n];
            $hasta = fmod($sig['hora'] - $p['hora'] + 24, 24) ?: 24;
            $ruta = self::camino([$antes['x'], $antes['y']], [$p['x'], $p['y']]);
            $caminar = min($hasta, self::largo($ruta) / self::VELOCIDAD / $segPorHora);
            if ($caminar > 0) {
                $tramos[] = ['desde' => (float) $p['hora'], 'dur' => $caminar, 'tipo' => 'caminar', 'ruta' => $ruta, 'actividad' => 'Caminando', 'vuelve' => null];
            }
            $tramos[] = ['desde' => fmod($p['hora'] + $caminar, 24), 'dur' => $hasta - $caminar, 'tipo' => empty($p['ausente']) ? 'quieto' : 'ausente',
                'x' => $p['x'], 'y' => $p['y'], 'dir' => $p['dir'] ?? 2, 'actividad' => $p['actividad'],
                'vuelve' => empty($p['ausente']) ? null : (float) $sig['hora']];
        }
        return self::$rutinas[$idContacto] = $tramos;
    }

    /** Dónde está un contacto a cierta hora del juego (0 a 24). */
    public static function posicion(string $idContacto, float $hora): array
    {
        foreach (self::rutina($idContacto) as $t) {
            $dentro = fmod($hora - $t['desde'] + 24, 24);
            if ($dentro >= $t['dur']) {
                continue;
            }
            if ($t['tipo'] !== 'caminar') {
                return ['x' => (int) $t['x'], 'y' => (int) $t['y'], 'dir' => (int) $t['dir'], 'estado' => $t['tipo'], 'actividad' => $t['actividad'], 'vuelve' => $t['vuelve']];
            }
            $objetivo = self::largo($t['ruta']) * ($dentro / $t['dur']);
            for ($i = 1; $i < count($t['ruta']); $i++) {
                [$p, $q] = [$t['ruta'][$i - 1], $t['ruta'][$i]];
                $l = hypot($q[0] - $p[0], $q[1] - $p[1]);
                if ($objetivo <= $l || $i === count($t['ruta']) - 1) {
                    $f = $l > 0 ? min(1, $objetivo / $l) : 0;
                    $dx = $q[0] - $p[0];
                    $dy = $q[1] - $p[1];
                    return ['x' => (int) round($p[0] + $dx * $f), 'y' => (int) round($p[1] + $dy * $f),
                        'dir' => abs($dx) > abs($dy) ? ($dx > 0 ? 1 : 3) : ($dy > 0 ? 2 : 0), 'estado' => 'caminando', 'actividad' => 'Caminando', 'vuelve' => null];
                }
                $objetivo -= $l;
            }
        }
        return ['x' => 0, 'y' => 0, 'dir' => 2, 'estado' => 'ausente', 'actividad' => '', 'vuelve' => null];
    }

    public static function horaActual(): float
    {
        return (float) MundoLogica::reloj(microtime(true))['hora'];
    }

    /* ---------- ánimo ---------- */

    public static function animo(string $idContacto, ?string $clima = null, ?float $hora = null): array
    {
        $reloj = MundoLogica::reloj(microtime(true));
        $clima ??= $reloj['clima'];
        $hora ??= (float) $reloj['hora'];
        $a = self::contacto($idContacto)['animo'] ?? ['base' => 0];
        $noche = $hora >= 20.5 || $hora < 5.5;
        $suceso = SucesoLogica::tipoDeTramo(SucesoLogica::tramo());
        $valor = max(-3, min(3, (int) ($a['base'] ?? 0) + (int) ($a['clima'][$clima] ?? 0) + ($noche ? (int) ($a['noche'] ?? 0) : 0)
            + ($suceso === null ? 0 : (int) ($a['sucesos'][$suceso] ?? 0))));
        $etiqueta = self::ETIQUETAS[$valor];
        return ['valor' => $valor, 'etiqueta' => $etiqueta, 'emocion' => self::EMOCION_POR_ANIMO[$etiqueta]];
    }

    /* ---------- confianza y memoria ---------- */

    public function confianza(int $idUsuario, string $idContacto): int
    {
        $v = $this->npc->confianza($idUsuario, $idContacto);
        if ($v !== null) {
            return $v;
        }
        $bando = $this->npc->bandoDe($idUsuario);
        $lado = self::contacto($idContacto)['lado'] ?? null;
        return $bando === null || $lado === null ? 0 : ($bando === $lado ? 10 : -10);
    }

    /** Suma (o resta) confianza. Si se pasa $unaVez, solo se aplica la primera vez. */
    public function cambiarConfianza(int $idUsuario, string $idContacto, int $delta, ?string $unaVez = null): int
    {
        if ($unaVez !== null && !$this->npc->recordar($idUsuario, $idContacto, $unaVez)) {
            return $this->confianza($idUsuario, $idContacto);
        }
        $nuevo = max(-100, min(100, $this->confianza($idUsuario, $idContacto) + $delta));
        $this->npc->guardarConfianza($idUsuario, $idContacto, $nuevo);
        return $nuevo;
    }

    public function recuerdos(int $idUsuario, string $idContacto): array
    {
        return array_values(array_filter($this->npc->recuerdos($idUsuario, $idContacto), fn($k) => !str_contains($k, ':')));
    }

    /* ---------- diálogos ---------- */

    private function cumple(array $cond, int $idUsuario, string $idContacto, array $animo): bool
    {
        $confianza = $this->confianza($idUsuario, $idContacto);
        $recuerdos = $this->npc->recuerdos($idUsuario, $idContacto);
        if (isset($cond['confianzaMin']) && $confianza < $cond['confianzaMin']) return false;
        if (isset($cond['confianzaMax']) && $confianza > $cond['confianzaMax']) return false;
        if (isset($cond['recuerdo']) && !in_array($cond['recuerdo'], $recuerdos, true)) return false;
        if (isset($cond['sinRecuerdo']) && in_array($cond['sinRecuerdo'], $recuerdos, true)) return false;
        if (isset($cond['bando']) && $this->npc->bandoDe($idUsuario) !== $cond['bando']) return false;
        if (isset($cond['animo']) && !in_array($animo['etiqueta'], (array) $cond['animo'], true)) return false;
        if (isset($cond['suceso']) && SucesoLogica::tipoDeTramo(SucesoLogica::tramo()) !== $cond['suceso']) return false;
        return true;
    }

    private function aplicar(array $efectos, int $idUsuario, string $idContacto, string $claveUnica): void
    {
        if (!empty($efectos['recuerdo'])) {
            $this->npc->recordar($idUsuario, $idContacto, $efectos['recuerdo']);
        }
        if (!empty($efectos['confianza'])) {
            $this->cambiarConfianza($idUsuario, $idContacto, (int) $efectos['confianza'], $claveUnica);
        }
    }

    /** Arma lo que se muestra de un nodo: texto según el ánimo, emoción y opciones disponibles. */
    private function nodo(string $idContacto, string $idNodo, int $idUsuario): array
    {
        $arbol = self::dialogos()[$idContacto];
        $n = $arbol['nodos'][$idNodo];
        $animo = self::animo($idContacto);
        $this->aplicar($n['efectos'] ?? [], $idUsuario, $idContacto, "dlg:$idContacto:$idNodo");
        $opciones = [];
        foreach ($n['opciones'] as $i => $o) {
            if ($this->cumple($o['cond'] ?? [], $idUsuario, $idContacto, $animo)) {
                $opciones[] = ['i' => $i, 'texto' => $o['texto']];
            }
        }
        return [
            'nodo' => $idNodo, 'texto' => $n['textoPorAnimo'][$animo['etiqueta']] ?? $n['texto'],
            'emocion' => $n['emocion'] ?? $animo['emocion'], 'opciones' => $opciones,
            'confianza' => $this->confianza($idUsuario, $idContacto), 'animo' => $animo['etiqueta'],
        ];
    }

    /** Primer nodo de la charla (la primera entrada cuya condición se cumple). Charlar una vez por día suma 1. */
    public function empezar(int $idUsuario, string $idContacto): array
    {
        $animo = self::animo($idContacto);
        $this->cambiarConfianza($idUsuario, $idContacto, 1, 'charla:' . date('Y-m-d'));
        foreach (self::dialogos()[$idContacto]['entradas'] as $e) {
            if ($this->cumple($e['cond'] ?? [], $idUsuario, $idContacto, $animo)) {
                return $this->nodo($idContacto, $e['nodo'], $idUsuario);
            }
        }
        return $this->nodo($idContacto, array_key_first(self::dialogos()[$idContacto]['nodos']), $idUsuario);
    }

    /** Elegir una opción de un nodo: se valida que exista y que su condición se cumpla. */
    public function responder(int $idUsuario, string $idContacto, string $idNodo, int $opcion): array
    {
        $arbol = self::dialogos()[$idContacto] ?? null;
        $o = $arbol['nodos'][$idNodo]['opciones'][$opcion] ?? null;
        if ($o === null || !$this->cumple($o['cond'] ?? [], $idUsuario, $idContacto, self::animo($idContacto))) {
            return ['exito' => false, 'mensaje' => 'Esa respuesta no está disponible.'];
        }
        $this->aplicar($o['efectos'] ?? [], $idUsuario, $idContacto, "dlg:$idContacto:$idNodo:$opcion");
        return ['exito' => true, 'dialogo' => $this->nodo($idContacto, $o['siguiente'], $idUsuario)];
    }
}
?>
