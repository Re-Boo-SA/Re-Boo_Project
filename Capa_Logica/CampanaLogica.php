<?php
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/CombateLogica.php');
require_once(__DIR__ . '/PartidaLogica.php');
require_once(__DIR__ . '/MisionLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * ======================================================================
 *  CAMPAÑA "NACHT ÜBER BERLIN" (etapa D)
 *
 *  Escenas propias de cada jugador (fuera del mundo compartido). El guion vive en
 *  datos/campana/<capitulo>.json y lo ejecuta js/campana.js. El servidor guarda:
 *   - hasta dónde llegó (puntos de "guardar", que tienen que ir en orden, sin saltear),
 *   - qué decidió (solo claves y valores que existen en el guion),
 *   - y cuándo terminó: hace falta haber llegado al último punto y haber jugado un mínimo
 *     de tiempo. El premio (XP y logro) se paga una sola vez.
 * ======================================================================
 */
class CampanaLogica
{
    /** Partes de la historia que el servidor resuelve y que no se pueden saltear. */
    public const PRUEBAS = ['combate', 'partida', 'interrogatorio', 'persecucion_auto', 'agujas'];

    private $campana;
    private $misiones;

    public function __construct()
    {
        $f = new FachadaPersistencia();
        $this->campana = $f->retornoIPersistenciaCampana();
        $this->misiones = $f->retornoIPersistenciaMisiones();
    }

    private static array $cache = [];

    private static function leer(string $archivo): ?array
    {
        if (!array_key_exists($archivo, self::$cache)) {
            $j = json_decode((string) @file_get_contents(dirname(__DIR__) . '/datos/campana/' . $archivo), true);
            self::$cache[$archivo] = is_array($j) ? $j : null;
        }
        return self::$cache[$archivo];
    }

    public static function capitulos(): array
    {
        return self::leer('capitulos.json')['capitulos'] ?? [];
    }

    /** El guion de un capítulo disponible (o null). El id se valida contra la lista: nunca se arma una ruta con lo que manda el navegador. */
    public static function guion(string $id): ?array
    {
        foreach (self::capitulos() as $c) {
            if ($c['id'] === $id && $c['disponible']) {
                return self::leer($id . '.json');
            }
        }
        return null;
    }

    /** Recorre los pasos (también los de adentro de un "si"). */
    private static function todosLosPasos(array $pasos): array
    {
        $r = [];
        foreach ($pasos as $p) {
            $r[] = $p;
            if ($p['tipo'] === 'si') {
                $r = array_merge($r, self::todosLosPasos($p['pasos']));
            }
            if ($p['tipo'] === 'paseo') {
                foreach ($p['puntos'] as $pt) $r = array_merge($r, self::todosLosPasos($pt['pasos']));
            }
        }
        return $r;
    }

    public static function puntos(array $guion): array
    {
        return array_values(array_map(fn($p) => $p['punto'], array_filter(self::todosLosPasos($guion['pasos']), fn($p) => $p['tipo'] === 'guardar')));
    }

    /** Mapas que usa el guion, con su contenido (el navegador no lee la carpeta datos/). */
    private static function mapas(array $guion): array
    {
        $r = [];
        foreach (self::todosLosPasos($guion['pasos']) as $p) {
            if ($p['tipo'] === 'escena' && !isset($r[$p['mapa']]) && preg_match('/^[a-z0-9_]+$/', $p['mapa'])) {
                $r[$p['mapa']] = self::leer('mapas/' . $p['mapa'] . '.json');
            }
        }
        return $r;
    }

    /** Lo que necesita el navegador para jugar: guion, mapas, progreso y decisiones. */
    public function capitulo(int $idUsuario, string $id): array
    {
        $g = self::guion($id);
        if ($g === null) {
            return ['exito' => false, 'mensaje' => 'Ese capítulo todavía no está disponible.'];
        }
        $progreso = $this->campana->progreso($idUsuario)[$id] ?? null;
        unset($g['_leeme']);
        return ['exito' => true, 'guion' => $g, 'mapas' => self::mapas($g), 'progreso' => $progreso, 'decisiones' => (object) $this->campana->decisiones($idUsuario)];
    }

    public function resumen(int $idUsuario): array
    {
        $p = $this->campana->progreso($idUsuario);
        return array_map(fn($c) => ['id' => $c['id'], 'titulo' => $c['titulo'], 'disponible' => $c['disponible'], 'habilitado' => $this->habilitado($idUsuario, $c['id']),
            'estado' => $p[$c['id']]['estado'] ?? null, 'punto' => $p[$c['id']]['punto'] ?? null], self::capitulos());
    }

    /** El capítulo anterior (o null si es el primero). */
    public static function anterior(string $id): ?string
    {
        $ids = array_column(self::capitulos(), 'id');
        $i = array_search($id, $ids, true);
        return $i === false || $i === 0 ? null : $ids[$i - 1];
    }

    /** Los capítulos se juegan en orden: hay que haber terminado el anterior. */
    private function habilitado(int $idUsuario, string $id): bool
    {
        $ant = self::anterior($id);
        return $ant === null || ($this->campana->progreso($idUsuario)[$ant]['estado'] ?? null) === 'completado';
    }

    /** Empezar o retomar. Si ya estaba completado, se puede volver a jugar (sin premio ni progreso nuevo). */
    public function empezar(int $idUsuario, string $id): array
    {
        if (self::guion($id) === null) {
            return ['exito' => false, 'mensaje' => 'Ese capítulo todavía no está disponible.'];
        }
        if (!$this->habilitado($idUsuario, $id)) {
            return ['exito' => false, 'mensaje' => 'Primero terminá el capítulo anterior.'];
        }
        $this->campana->empezar($idUsuario, $id);
        return ['exito' => true, 'progreso' => $this->campana->progreso($idUsuario)[$id]];
    }

    /** Punto de guardado: tiene que existir y ser el actual o el siguiente (no se puede saltear). */
    public function guardar(int $idUsuario, string $id, string $punto): array
    {
        $g = self::guion($id);
        $p = $this->campana->progreso($idUsuario)[$id] ?? null;
        if ($g === null || $p === null) {
            return ['exito' => false, 'mensaje' => 'Primero empezá el capítulo.'];
        }
        if ($p['estado'] === 'completado') {
            return ['exito' => true];   // volver a jugar uno terminado no cambia nada
        }
        $puntos = self::puntos($g);
        $nuevo = array_search($punto, $puntos, true);
        $actual = $p['punto'] === null ? -1 : array_search($p['punto'], $puntos, true);
        if ($nuevo === false || $nuevo > $actual + 1) {
            return ['exito' => false, 'mensaje' => 'Ese punto de la historia no corresponde todavía.'];
        }
        if ($nuevo > $actual) {
            foreach (self::combatesEntre($g, $actual < 0 ? null : $puntos[$actual], $punto, $this->campana->decisiones($idUsuario)) as [$idPrueba, $tipo]) {
                if (($this->campana->combate($idUsuario, $id, $idPrueba)['resultado'] ?? null) !== 'victoria') {
                    return ['exito' => false, 'mensaje' => $tipo === 'combate' ? 'Primero hay que ganar el combate.' : 'Primero hay que superar esa parte de la historia.'];
                }
            }
        }
        if ($nuevo > $actual) {
            $this->campana->guardarPunto($idUsuario, $id, $punto);
        }
        return ['exito' => true];
    }

    /** Pasos en orden, entrando solo en los "si" de la rama en la que está el jugador. */
    private static function pasosDeLaRama(array $pasos, array $decisiones): array
    {
        $r = [];
        foreach ($pasos as $p) {
            $r[] = $p;
            $tienePrueba = $p['tipo'] === 'si' && array_filter(self::todosLosPasos($p['pasos']), fn($q) => in_array($q['tipo'], self::PRUEBAS, true));
            if ($p['tipo'] === 'si' && $tienePrueba && !array_key_exists($p['clave'], $decisiones)) {
                // Una rama con una prueba y sin decidir todavía: no se puede pasar de largo (si no, se saltearía la prueba)
                $r[] = ['tipo' => 'decision_pendiente', 'id' => 'decision:' . $p['clave']];
            } elseif ($p['tipo'] === 'si' && ($decisiones[$p['clave']] ?? null) === $p['valor']) {
                $r = array_merge($r, self::pasosDeLaRama($p['pasos'], $decisiones));
            }
        }
        return $r;
    }

    /** Pruebas del guion que quedan entre dos puntos de guardado (solo las de la rama del jugador). */
    private static function combatesEntre(array $guion, ?string $desde, string $hasta, array $decisiones = []): array
    {
        $r = [];
        $adentro = $desde === null;
        foreach (self::pasosDeLaRama($guion['pasos'], $decisiones) as $p) {
            if ($p['tipo'] === 'guardar') {
                if ($p['punto'] === $hasta) break;
                if ($p['punto'] === $desde) $adentro = true;
            } elseif ($adentro && (in_array($p['tipo'], self::PRUEBAS, true) || $p['tipo'] === 'decision_pendiente')) {
                $r[] = [$p['id'], $p['tipo']];
            }
        }
        return $r;
    }

    /* ---------- combate táctico ---------- */

    private function definicionCombate(string $id, string $idCombate): ?array
    {
        return $this->definicionPaso($id, $idCombate, 'combate');
    }

    private function definicionPaso(string $id, string $idPaso, string $tipo): ?array
    {
        $g = self::guion($id);
        foreach ($g === null ? [] : self::todosLosPasos($g['pasos']) as $p) {
            if ($p['tipo'] === $tipo && ($p['id'] ?? null) === $idPaso) return $p;
        }
        return null;
    }

    private function empezado(int $idUsuario, string $id): bool
    {
        return ($this->campana->progreso($idUsuario)[$id] ?? null) !== null;
    }

    /* ---------- una partida de Draft Der Mauer dentro de la historia (Cap. 1: la mesa de Otto) ---------- */

    private static function clavePartida(string $id, string $idPaso): string
    {
        return 'campana:' . $id . ':' . $idPaso;   // se guarda como los desafíos de las misiones
    }

    public function partidaIniciar(int $idUsuario, string $id, string $idPaso): array
    {
        $def = $this->definicionPaso($id, $idPaso, 'partida');
        if ($def === null || !$this->empezado($idUsuario, $id)) return ['exito' => false, 'mensaje' => 'Esa partida no existe o el capítulo no empezó.'];
        $partidas = new PartidaLogica();
        $actual = $partidas->partidaActiva($idUsuario);
        if ($actual !== null) {
            $mia = array_filter($this->misiones->intentos($idUsuario, self::clavePartida($id, $idPaso), 0), fn($i) => (int) $i['IDPartida'] === $actual);
            return $mia ? ['exito' => true, 'idPartida' => $actual] : ['exito' => false, 'mensaje' => 'Terminá la partida en la que estás.'];
        }
        $config = ConfigPartida::desdeArreglo(($def['reglas'] ?? []) + ['permiteBots' => 1]);
        if (($error = $config->validar()) !== null) return ['exito' => false, 'mensaje' => $error];
        $this->misiones->iniciarTransaccion();
        try {
            $idPartida = $partidas->crearDesafio($idUsuario, (int) $def['bot'], $config, mb_substr($def['nombre'] ?? 'La mesa', 0, 40), $def['escenario'] ?? []);
            $this->misiones->registrarIntento($idPartida, $idUsuario, self::clavePartida($id, $idPaso), 0);
            $this->misiones->confirmar();
        } catch (Throwable $e) {
            $this->misiones->revertir();
            throw $e;
        }
        $this->campana->guardarCombate($idUsuario, $id, $idPaso, json_encode(['partida' => $idPartida]), null, true);
        return ['exito' => true, 'idPartida' => $idPartida];
    }

    /** Cómo salió la última partida: sin_empezar, en_juego, ganada o perdida. Si se ganó, queda anotado para poder seguir. */
    public function partidaEstado(int $idUsuario, string $id, string $idPaso): array
    {
        if ($this->definicionPaso($id, $idPaso, 'partida') === null) return ['exito' => false, 'mensaje' => 'Esa partida no existe.'];
        if (($this->campana->combate($idUsuario, $id, $idPaso)['resultado'] ?? null) === 'victoria') return ['exito' => true, 'estado' => 'ganada'];
        $ultimo = $this->misiones->intentos($idUsuario, self::clavePartida($id, $idPaso), 0)[0] ?? null;
        if ($ultimo === null) return ['exito' => true, 'estado' => 'sin_empezar'];
        $r = $this->misiones->resultadoDesafio((int) $ultimo['IDPartida'], $idUsuario);
        if ($r !== null && in_array($r['EstadoPartida'], ['esperando', 'en_curso'], true)) return ['exito' => true, 'estado' => 'en_juego', 'idPartida' => (int) $ultimo['IDPartida']];
        $gano = $r !== null && $r['EstadoPartida'] === 'finalizada' && $r['EstadoJugador'] === 'activo' && (int) $r['Posicion'] === 1;
        $this->misiones->marcarIntento((int) $ultimo['IDPartida'], $gano ? 'ganada' : 'perdida');
        if ($gano) $this->campana->guardarCombate($idUsuario, $id, $idPaso, json_encode(['partida' => (int) $ultimo['IDPartida']]), 'victoria', false);
        return ['exito' => true, 'estado' => $gano ? 'ganada' : 'perdida'];
    }

    /* ---------- interrogatorio: preguntas con tiempo y un medidor de sospecha ---------- */

    /** $respuestas: el índice elegido en cada pregunta, o -1 si se acabó el tiempo. */
    public function interrogatorio(int $idUsuario, string $id, string $idPaso, array $respuestas): array
    {
        $def = $this->definicionPaso($id, $idPaso, 'interrogatorio');
        if ($def === null || !$this->empezado($idUsuario, $id)) return ['exito' => false, 'mensaje' => 'Ese interrogatorio no existe o el capítulo no empezó.'];
        if (count($respuestas) !== count($def['preguntas'])) return ['exito' => false, 'mensaje' => 'Faltan respuestas.'];
        $sospecha = 0;
        foreach ($def['preguntas'] as $i => $q) {
            $r = (int) $respuestas[$i];
            if ($r !== -1 && !isset($q['opciones'][$r])) return ['exito' => false, 'mensaje' => 'Esa respuesta no existe.'];
            $sospecha += $r === -1 ? (int) $def['sinRespuesta'] : (int) $q['opciones'][$r]['sospecha'];
        }
        $superado = $sospecha < (int) $def['limite'];
        $this->campana->guardarCombate($idUsuario, $id, $idPaso, json_encode(['respuestas' => $respuestas, 'sospecha' => $sospecha]), $superado ? 'victoria' : 'derrota', true);
        if ($superado) $this->campana->guardarCombate($idUsuario, $id, $idPaso, json_encode(['respuestas' => $respuestas, 'sospecha' => $sospecha]), 'victoria', false);
        return ['exito' => true, 'superado' => $superado, 'sospecha' => $sospecha, 'limite' => (int) $def['limite']];
    }

    /* ---------- agujas: el acertijo de los cambios de vía (Cap. 6) ---------- */

    /** Adónde va el tren con estas agujas: se recorre el plano desde la entrada. */
    public static function destinoAgujas(array $def, array $estados): ?string
    {
        $nodo = $def['inicio'];
        for ($i = 0; $i < 20 && isset($def['agujas'][$nodo]); $i++) {
            $nodo = $def['agujas'][$nodo][$estados[$nodo] ?? $def['agujas'][$nodo]['estado']];
        }
        return isset($def['agujas'][$nodo]) ? null : $nodo;
    }

    /** $estados: ['A' => 'recta'|'desvio', ...] como quedaron las palancas. Superado si el tren llega a la meta. */
    public function agujas(int $idUsuario, string $id, string $idPaso, array $estados): array
    {
        $def = $this->definicionPaso($id, $idPaso, 'agujas');
        if ($def === null || !$this->empezado($idUsuario, $id)) return ['exito' => false, 'mensaje' => 'Esas agujas no existen o el capítulo no empezó.'];
        if (array_diff(array_keys($def['agujas']), array_keys($estados)) || array_diff(array_keys($estados), array_keys($def['agujas']))) return ['exito' => false, 'mensaje' => 'Faltan agujas.'];
        foreach ($estados as $v) if (!in_array($v, ['recta', 'desvio'], true)) return ['exito' => false, 'mensaje' => 'Faltan agujas.'];
        $destino = self::destinoAgujas($def, $estados);
        $bien = $destino === $def['meta'];
        $this->campana->guardarCombate($idUsuario, $id, $idPaso, json_encode(['estados' => $estados, 'destino' => $destino]), $bien ? 'victoria' : 'derrota', true);
        if ($bien) $this->campana->guardarCombate($idUsuario, $id, $idPaso, json_encode(['estados' => $estados, 'destino' => $destino]), 'victoria', false);
        return ['exito' => true, 'superado' => $bien, 'destino' => $destino];
    }

    /* ---------- persecución en auto: tramos con decisiones de ruta y tiempo límite ---------- */

    public function persecucionIniciar(int $idUsuario, string $id, string $idPaso): array
    {
        $def = $this->definicionPaso($id, $idPaso, 'persecucion_auto');
        if ($def === null || !$this->empezado($idUsuario, $id)) return ['exito' => false, 'mensaje' => 'Esa persecución no existe o el capítulo no empezó.'];
        $e = ['tramo' => 0, 'ventaja' => (int) $def['ventaja'], 'alivio' => 0, 'semilla' => random_int(1, PHP_INT_MAX), 'tiradas' => 0, 'resultado' => null];
        $this->campana->guardarCombate($idUsuario, $id, $idPaso, json_encode($e), null, true);
        return ['exito' => true, 'persecucion' => ['tramo' => 0, 'ventaja' => $e['ventaja'], 'tramos' => count($def['tramos']), 'resultado' => null]];
    }

    /** $opcion: índice elegido, o -1 si se acabó el tiempo (lo peor: se pierde terreno). El azar lo pone el servidor. */
    public function persecucionElegir(int $idUsuario, string $id, string $idPaso, int $opcion): array
    {
        $def = $this->definicionPaso($id, $idPaso, 'persecucion_auto');
        $fila = $this->campana->combate($idUsuario, $id, $idPaso);
        if ($def === null || $fila === null) return ['exito' => false, 'mensaje' => 'Primero empezá la persecución.'];
        $e = $fila['estado'];
        if ($e['resultado'] !== null) return ['exito' => false, 'mensaje' => 'La persecución ya terminó.'];
        $tramo = $def['tramos'][$e['tramo']];
        if ($opcion !== -1 && !isset($tramo['opciones'][$opcion])) return ['exito' => false, 'mensaje' => 'Esa opción no existe.'];
        if ($opcion === -1) {
            $acierto = false; $cambio = -2; $texto = $def['sinDecidir'];
        } else {
            $o = $tramo['opciones'][$opcion];
            $tirada = crc32($e['semilla'] . ':' . ($e['tiradas']++)) % 100;
            $acierto = $tirada >= max(0, (int) $o['riesgo'] - $e['alivio']);
            $cambio = $acierto ? (int) ($o['ganancia'] ?? 1) : -(int) ($o['perdida'] ?? 1);
            $texto = $acierto ? $o['bien'] : $o['mal'];
        }
        $e['alivio'] = $opcion !== -1 && !empty($tramo['opciones'][$opcion]['radio']) ? 25 : 0;   // la radio de Thomas confunde a los perseguidores
        $e['ventaja'] += $cambio;
        $e['tramo']++;
        if ($e['ventaja'] <= 0) $e['resultado'] = 'derrota';
        elseif ($e['tramo'] >= count($def['tramos'])) $e['resultado'] = 'victoria';
        $this->campana->guardarCombate($idUsuario, $id, $idPaso, json_encode($e), $e['resultado'], false);
        return ['exito' => true, 'persecucion' => ['tramo' => $e['tramo'], 'ventaja' => $e['ventaja'], 'tramos' => count($def['tramos']), 'resultado' => $e['resultado'],
            'acierto' => $acierto, 'texto' => $texto, 'cambio' => $cambio]];
    }

    /** Empezar (o reintentar) un combate: estado nuevo con una semilla que solo conoce el servidor. */
    public function combateIniciar(int $idUsuario, string $id, string $idCombate): array
    {
        $def = $this->definicionCombate($id, $idCombate);
        if ($def === null || ($this->campana->progreso($idUsuario)[$id] ?? null) === null) {
            return ['exito' => false, 'mensaje' => 'Ese combate no existe o el capítulo no empezó.'];
        }
        $e = CombateLogica::nuevo($def, self::leer('mapas/' . $def['mapa'] . '.json'), random_int(1, PHP_INT_MAX));
        $this->campana->guardarCombate($idUsuario, $id, $idCombate, json_encode($e), null, true);
        return ['exito' => true, 'combate' => CombateLogica::vista($e)];
    }

    /** Una acción de un personaje: mover (x, y), disparar (blanco) o cubrirse. */
    public function combateAccion(int $idUsuario, string $id, string $idCombate, string $unidad, string $tipo, int $x, int $y, string $blanco): array
    {
        $c = $this->campana->combate($idUsuario, $id, $idCombate);
        if ($c === null) return ['exito' => false, 'mensaje' => 'Primero empezá el combate.'];
        $e = $c['estado'];
        $e['eventos'] = [];
        $error = match ($tipo) {
            'mover' => CombateLogica::mover($e, $unidad, $x, $y),
            'disparar' => CombateLogica::disparar($e, $unidad, $blanco),
            'cubrirse' => CombateLogica::cubrirse($e, $unidad),
            default => 'Acción desconocida.',
        };
        if ($error !== null) return ['exito' => false, 'mensaje' => $error];
        $this->campana->guardarCombate($idUsuario, $id, $idCombate, json_encode($e), $e['resultado'], false);
        return ['exito' => true, 'combate' => CombateLogica::vista($e)];
    }

    public function combateTurno(int $idUsuario, string $id, string $idCombate): array
    {
        $c = $this->campana->combate($idUsuario, $id, $idCombate);
        if ($c === null) return ['exito' => false, 'mensaje' => 'Primero empezá el combate.'];
        $e = $c['estado'];
        $e['eventos'] = [];
        if (($error = CombateLogica::terminarTurno($e)) !== null) return ['exito' => false, 'mensaje' => $error];
        $this->campana->guardarCombate($idUsuario, $id, $idCombate, json_encode($e), $e['resultado'], false);
        return ['exito' => true, 'combate' => CombateLogica::vista($e)];
    }

    /** Guardar una decisión: la clave y el valor tienen que existir en un paso "elegir" del capítulo. */
    public function decidir(int $idUsuario, string $id, string $clave, string $valor): array
    {
        $g = self::guion($id);
        if ($g !== null) {
            foreach (self::todosLosPasos($g['pasos']) as $p) {
                $sigilo = $p['tipo'] === 'sigilo' && ($p['registra'] ?? null) === $clave && in_array($valor, ['si', 'no'], true);
                if ($sigilo || ($p['tipo'] === 'elegir' && $p['clave'] === $clave && in_array($valor, array_column($p['opciones'], 'valor'), true))) {
                    $this->campana->decidir($idUsuario, $clave, $valor);
                    return ['exito' => true];
                }
            }
        }
        return ['exito' => false, 'mensaje' => 'Esa decisión no existe.'];
    }

    /** Terminar: haber llegado al último punto y jugado el mínimo de tiempo. El premio se paga una vez. */
    public function completar(int $idUsuario, string $id): array
    {
        $g = self::guion($id);
        $p = $this->campana->progreso($idUsuario)[$id] ?? null;
        if ($g === null || $p === null) {
            return ['exito' => false, 'mensaje' => 'Primero empezá el capítulo.'];
        }
        if ($p['estado'] === 'completado') {
            // Volver a jugar: no se paga de nuevo, pero el logro de la decisión (por ejemplo, otro final) sí se gana
            return ['exito' => true, 'yaCompletado' => true, 'logrosDecision' => $this->logrosPorDecision($idUsuario, $g), 'siguiente' => self::siguiente($id), 'ultimo' => self::esUltimo($id)];
        }
        $puntos = self::puntos($g);
        if ($p['punto'] !== end($puntos)) {
            return ['exito' => false, 'mensaje' => 'Todavía no llegaste al final.'];
        }
        if (($this->campana->segundosDesdeInicio($idUsuario, $id) ?? 0) < (int) ($g['duracionMinima'] ?? 0)) {
            return ['exito' => false, 'mensaje' => 'Fue demasiado rápido.'];
        }
        if (!$this->campana->completar($idUsuario, $id)) {
            return ['exito' => true, 'yaCompletado' => true, 'siguiente' => self::siguiente($id)];
        }
        $r = $g['recompensa'] ?? [];
        $this->misiones->sumarRecompensa($idUsuario, (int) ($r['xp'] ?? 0), 0);
        if (!empty($r['logro'])) {
            $this->misiones->otorgarLogro($idUsuario, $r['logro']);
        }
        $porDecision = $this->logrosPorDecision($idUsuario, $g);
        (new AuditoriaLogica())->registrar('campana_capitulo', 'info', $idUsuario, null, $id);
        $logro = array_values(array_filter(MisionLogica::datos()['logros'], fn($l) => $l['id'] === ($r['logro'] ?? '')))[0]['nombre'] ?? null;
        return ['exito' => true, 'xp' => (int) ($r['xp'] ?? 0), 'logro' => $logro, 'logrosDecision' => $porDecision, 'siguiente' => self::siguiente($id), 'ultimo' => self::esUltimo($id)];
    }

    /** Logros que dependen de una decisión del capítulo (por ejemplo, uno por cada final). Devuelve sus nombres. */
    private function logrosPorDecision(int $idUsuario, array $g): array
    {
        $nombres = [];
        $decisiones = $this->campana->decisiones($idUsuario);
        $todos = array_column(MisionLogica::datos()['logros'], 'nombre', 'id');
        foreach ($g['logrosPorDecision'] ?? [] as $clave => $mapa) {
            $logro = $mapa[$decisiones[$clave] ?? ''] ?? null;
            if ($logro !== null && isset($todos[$logro])) {
                $this->misiones->otorgarLogro($idUsuario, $logro);
                $nombres[] = $todos[$logro];
            }
        }
        return $nombres;
    }

    /** ¿Es el último capítulo de la campaña? */
    public static function esUltimo(string $id): bool
    {
        $lista = self::capitulos();
        return $lista !== [] && end($lista)['id'] === $id;
    }

    /** El capítulo que sigue, si ya se puede jugar. */
    public static function siguiente(string $id): ?array
    {
        $lista = self::capitulos();
        foreach ($lista as $i => $c) {
            if ($c['id'] === $id && isset($lista[$i + 1]) && $lista[$i + 1]['disponible']) return ['id' => $lista[$i + 1]['id'], 'titulo' => $lista[$i + 1]['titulo']];
        }
        return null;
    }
}
?>
