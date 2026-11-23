<?php
require_once(__DIR__ . '/ITorneoLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * ======================================================================
 *  TORNEOS RELÁMPAGO (cierre de la Fase 4)
 *
 *  - Los arma un jugador (uno activo por vez): 4 u 8 lugares y hora de inicio
 *    (15, 30 o 60 minutos). Empieza antes si se llena; a la hora, con 2 o más.
 *  - Cuadro de eliminación directa sembrado por MMR (1 contra el último...).
 *    Si faltan jugadores, los mejores sembrados pasan sin jugar.
 *  - Cada cruce es una partida de 2 con reglas oficiales (se puede mirar).
 *  - El cuadro avanza "a demanda": cuando alguien mira el torneo o un inscripto
 *    está en Berlín. Se trabaja con la fila del torneo bloqueada (FOR UPDATE).
 *  - Quien abandona o no se presenta pierde (el sistema de abandono ya existe).
 *    Empate o partida cancelada: pasa el mejor sembrado.
 *  - Premio: logro "Campeón de Berlín", 200 XP (finalista: 80) y la cuenta de
 *    torneos ganados en el perfil. Nada que dé ventaja en las partidas.
 * ======================================================================
 */
class TorneoLogica implements ITorneoLogica
{
    public const TAMANOS = [4, 8];
    public const MINUTOS = [15, 30, 60];
    public const XP_CAMPEON = 200;
    public const XP_FINALISTA = 80;
    /** Orden de las semillas en el cuadro (los mejores no se cruzan hasta el final). */
    private const CUADRO = [2 => [1, 2], 4 => [1, 4, 2, 3], 8 => [1, 8, 4, 5, 2, 7, 3, 6]];

    private $torneos;
    private $misiones;

    public function __construct()
    {
        $f = new FachadaPersistencia();
        $this->torneos = $f->retornoIPersistenciaTorneos();
        $this->misiones = $f->retornoIPersistenciaMisiones();
    }

    private static function ok(array $extra = []): array { return ['exito' => true] + $extra; }
    private static function error(string $m): array { return ['exito' => false, 'mensaje' => $m]; }

    /* ---------- armar e inscribirse ---------- */

    public function crear(int $idUsuario, string $nombre, int $tamano, int $minutos): array
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? '');
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 30) {
            return self::error('El nombre tiene que tener entre 3 y 30 caracteres.');
        }
        if (!in_array($tamano, self::TAMANOS, true) || !in_array($minutos, self::MINUTOS, true)) {
            return self::error('Elegí 4 u 8 jugadores y empezar en 15, 30 o 60 minutos.');
        }
        if ($this->torneos->activosCreadosPor($idUsuario) > 0) {
            return self::error('Ya tenés un torneo armado. Esperá a que termine para armar otro.');
        }
        $id = $this->torneos->crear($nombre, $tamano, $idUsuario, $minutos);
        $this->torneos->inscribir($id, $idUsuario);
        (new AuditoriaLogica())->registrar('torneo_creado', 'info', $idUsuario, null, "Torneo $id ($tamano)");
        return self::ok(['idTorneo' => $id, 'mensaje' => 'Torneo armado. Ya estás inscripto.']);
    }

    public function inscribir(int $idUsuario, int $idTorneo): array
    {
        $this->torneos->iniciarTransaccion();
        try {
            $t = $this->torneos->torneo($idTorneo, true);
            if ($t === null || $t['Estado'] !== 'inscripcion') {
                $this->torneos->revertir();
                return self::error('Ese torneo ya no acepta inscripciones.');
            }
            $ins = $this->torneos->inscriptos($idTorneo);
            if (in_array($idUsuario, array_map('intval', array_column($ins, 'IDUsuario')), true)) {
                $this->torneos->revertir();
                return self::error('Ya estás inscripto.');
            }
            if (count($ins) >= (int) $t['Tamano']) {
                $this->torneos->revertir();
                return self::error('El torneo está completo.');
            }
            $this->torneos->inscribir($idTorneo, $idUsuario);
            $this->torneos->confirmar();
        } catch (Throwable $e) {
            $this->torneos->revertir();
            throw $e;
        }
        $this->avanzar($idTorneo);   // si se llenó, arranca ya
        return self::ok(['mensaje' => 'Inscripto.']);
    }

    public function salir(int $idUsuario, int $idTorneo): array
    {
        $t = $this->torneos->torneo($idTorneo);
        if ($t === null || $t['Estado'] !== 'inscripcion') {
            return self::error('Solo te podés bajar antes de que empiece.');
        }
        $this->torneos->desinscribir($idTorneo, $idUsuario);
        if ((int) $t['IDCreador'] === $idUsuario && $this->torneos->inscriptos($idTorneo) === []) {
            $this->torneos->cambiarEstado($idTorneo, 'cancelado', 0, null);
        }
        return self::ok(['mensaje' => 'Te bajaste del torneo.']);
    }

    /* ---------- el cuadro ---------- */

    /** Tamaño del cuadro: la menor potencia de 2 que alcanza para los inscriptos (2, 4 u 8). */
    private static function tamanoCuadro(int $jugadores): int
    {
        return $jugadores <= 2 ? 2 : ($jugadores <= 4 ? 4 : 8);
    }

    /**
     * Antes de tomar la fila del torneo: a quien tiene que jugar y está sentado en una sala que
     * todavía no empezó, lo saca de esa sala (si no, perdería por no presentarse).
     * Va FUERA de la transacción del torneo porque salir de una sala abre la suya propia.
     */
    private function liberarJugadores(int $idTorneo): void
    {
        $partidas = new PartidaLogica();
        foreach ($this->torneos->inscriptos($idTorneo) as $j) {
            if ((bool) $j['Eliminado']) {
                continue;
            }
            $id = (int) $j['IDUsuario'];
            $sala = $partidas->partidaActiva($id);
            if ($sala !== null && $this->torneos->torneoDePartida($sala) === null) {
                $r = $this->torneos->resultadoPartida($sala);
                if ($r !== null && $r['estado'] === 'esperando') {
                    $partidas->accion($sala, $id, 'salir', []);
                }
            }
        }
    }

    /** ¿Hay algo para hacer? (se mira sin bloquear, para no tomar la fila en cada consulta). */
    private function hayQueAvanzar(array $t): bool
    {
        if ($t['Estado'] === 'inscripcion') {
            return (bool) $t['Vencido'] || count($this->torneos->inscriptos((int) $t['IDTorneo'])) >= (int) $t['Tamano'];
        }
        if ($t['Estado'] !== 'en_curso') {
            return false;
        }
        $sinResolver = array_filter($this->torneos->llaves((int) $t['IDTorneo']),
            fn($l) => (int) $l['Ronda'] === (int) $t['Ronda'] && $l['IDGanador'] === null);
        if ($sinResolver === []) {
            return true;   // ronda completa: hay que armar la siguiente (o coronar)
        }
        foreach ($sinResolver as $l) {
            if (!in_array($l['EstadoPartida'], ['en_curso', 'esperando'], true)) {
                return true;   // un cruce terminó: se resuelve ya, sin esperar a los demás
            }
        }
        return false;
    }

    public function avanzar(int $idTorneo): void
    {
        $t = $this->torneos->torneo($idTorneo);
        if ($t === null || !$this->hayQueAvanzar($t)) {
            return;
        }
        $this->liberarJugadores($idTorneo);
        $premios = [];
        $this->torneos->iniciarTransaccion();
        try {
            $t = $this->torneos->torneo($idTorneo, true);
            if ($t['Estado'] === 'inscripcion') {
                $this->empezar($t);
            } elseif ($t['Estado'] === 'en_curso') {
                $premios = $this->cerrarRonda($t);
            }
            $this->torneos->confirmar();
        } catch (Throwable $e) {
            $this->torneos->revertir();
            throw $e;
        }
        foreach ($premios as [$idUsuario, $xp, $logro]) {
            $this->misiones->sumarRecompensa($idUsuario, $xp, 0);
            if ($logro) {
                $this->misiones->otorgarLogro($idUsuario, 'campeon_torneo');
            }
        }
    }

    private function empezar(array $t): void
    {
        $id = (int) $t['IDTorneo'];
        $ins = $this->torneos->inscriptos($id);
        if (count($ins) < (int) $t['Tamano'] && !(bool) $t['Vencido']) {
            return;
        }
        if (count($ins) < 2) {
            $this->torneos->cambiarEstado($id, 'cancelado', 0, null);
            return;
        }
        usort($ins, fn($a, $b) => (int) $b['MMR'] <=> (int) $a['MMR'] ?: (int) $a['IDUsuario'] <=> (int) $b['IDUsuario']);
        $porSemilla = [];
        foreach ($ins as $i => $j) {
            $this->torneos->fijarSemilla($id, (int) $j['IDUsuario'], $i + 1);
            $porSemilla[$i + 1] = (int) $j['IDUsuario'];
        }
        $orden = self::CUADRO[self::tamanoCuadro(count($ins))];
        $jugadores = array_map(fn($s) => $porSemilla[$s] ?? null, $orden);
        $this->torneos->cambiarEstado($id, 'en_curso', 1, null);
        $this->armarRonda($t, 1, $jugadores);
    }

    /** Arma los cruces de una ronda a partir de la lista ordenada de jugadores (null = lugar vacío). */
    private function armarRonda(array $t, int $ronda, array $jugadores): void
    {
        $id = (int) $t['IDTorneo'];
        $semillas = array_column($this->torneos->inscriptos($id), 'Semilla', 'IDUsuario');
        $partidas = new PartidaLogica();
        for ($i = 0; $i < count($jugadores); $i += 2) {
            [$a, $b] = [$jugadores[$i], $jugadores[$i + 1]];
            if ($a === null) {
                [$a, $b] = [$b, $a];
            }
            $pos = intdiv($i, 2);
            if ($b === null) {
                $this->torneos->crearLlave($id, $ronda, $pos, $a, null, null, $a, 'sin_rival');
                continue;
            }
            $ocupadoA = $partidas->partidaActiva($a) !== null;
            $ocupadoB = $partidas->partidaActiva($b) !== null;
            if ($ocupadoA || $ocupadoB) {
                // Está jugando otra partida: pierde por no presentarse (si los dos, pasa el mejor sembrado).
                $ganador = $ocupadoA && !$ocupadoB ? $b : (!$ocupadoA && $ocupadoB ? $a : ((int) $semillas[$a] <= (int) $semillas[$b] ? $a : $b));
                $this->torneos->crearLlave($id, $ronda, $pos, $a, $b, null, $ganador, 'no_se_presento');
                continue;
            }
            $nombre = mb_substr('Torneo ' . $t['Nombre'], 0, 30) . ' · R' . $ronda;
            $idPartida = $partidas->crearDeTorneo([$a, $b], $nombre);
            $this->torneos->crearLlave($id, $ronda, $pos, $a, $b, $idPartida, null, null);
        }
    }

    /** Resuelve los cruces terminados; si la ronda está completa, arma la siguiente o corona al campeón. */
    private function cerrarRonda(array $t): array
    {
        $id = (int) $t['IDTorneo'];
        $ronda = (int) $t['Ronda'];
        $semillas = array_column($this->torneos->inscriptos($id), 'Semilla', 'IDUsuario');
        $mejor = fn(int $a, int $b) => (int) $semillas[$a] <= (int) $semillas[$b] ? $a : $b;
        $deLaRonda = array_values(array_filter($this->torneos->llaves($id), fn($l) => (int) $l['Ronda'] === $ronda));
        $ganadores = [];
        $faltan = false;
        foreach ($deLaRonda as $l) {
            $ganador = $l['IDGanador'] === null ? null : (int) $l['IDGanador'];
            if ($ganador === null) {
                $r = $this->torneos->resultadoPartida((int) $l['IDPartida']);
                $a = (int) $l['IDJugadorA'];
                $b = (int) $l['IDJugadorB'];
                if ($r === null || in_array($r['estado'], ['en_curso', 'esperando'], true)) {
                    $faltan = true;   // este cruce sigue: los terminados se resuelven igual
                    continue;
                }
                $pa = $r['posiciones'][$a] ?? null;
                $pb = $r['posiciones'][$b] ?? null;
                if ($r['estado'] === 'finalizada' && $pa !== null && ($pb === null || (int) $pa < (int) $pb)) {
                    $ganador = $a;
                    $resultado = 'partida';
                } elseif ($r['estado'] === 'finalizada' && $pb !== null && ($pa === null || (int) $pb < (int) $pa)) {
                    $ganador = $b;
                    $resultado = 'partida';
                } else {
                    $ganador = $mejor($a, $b);   // empate o partida cancelada
                    $resultado = 'desempate';
                }
                $this->torneos->resolverLlave($id, $ronda, (int) $l['Posicion'], $ganador, $resultado);
            }
            foreach ([(int) $l['IDJugadorA'], $l['IDJugadorB'] === null ? null : (int) $l['IDJugadorB']] as $j) {
                if ($j !== null && $j !== $ganador) {
                    $this->torneos->eliminar($id, $j);
                }
            }
            $ganadores[] = $ganador;
        }
        if ($faltan) {
            return [];   // la ronda siguiente se arma cuando terminen todos los cruces
        }
        if (count($ganadores) === 1) {
            $this->torneos->cambiarEstado($id, 'finalizado', $ronda, $ganadores[0]);
            $final = $deLaRonda[0];
            $finalista = (int) $final['IDJugadorA'] === $ganadores[0] ? $final['IDJugadorB'] : $final['IDJugadorA'];
            (new AuditoriaLogica())->registrar('torneo_ganado', 'info', $ganadores[0], null, "Torneo $id");
            return array_values(array_filter([[$ganadores[0], self::XP_CAMPEON, true], $finalista === null ? null : [(int) $finalista, self::XP_FINALISTA, false]]));
        }
        $this->torneos->cambiarEstado($id, 'en_curso', $ronda + 1, null);
        $this->armarRonda($t, $ronda + 1, $ganadores);
        return [];
    }

    /* ---------- lo que se muestra ---------- */

    private function resumen(array $t, int $idUsuario, array $misTorneos): array
    {
        return [
            'id' => (int) $t['IDTorneo'], 'nombre' => $t['Nombre'], 'tamano' => (int) $t['Tamano'], 'estado' => $t['Estado'],
            'ronda' => (int) $t['Ronda'], 'creador' => $t['Creador'], 'ganador' => $t['Ganador'],
            'inscriptos' => (int) ($t['Inscriptos'] ?? count($this->torneos->inscriptos((int) $t['IDTorneo']))),
            'segundosParaEmpezar' => max(0, (int) $t['SegundosParaEmpezar']),
            'inscripto' => in_array((int) $t['IDTorneo'], $misTorneos, true),
        ];
    }

    public function listar(int $idUsuario): array
    {
        foreach ($this->torneos->listar() as $t) {
            if (in_array($t['Estado'], ['inscripcion', 'en_curso'], true)) {
                $this->avanzar((int) $t['IDTorneo']);
            }
        }
        $mios = $this->torneos->torneosActivosDe($idUsuario);
        return self::ok(['torneos' => array_map(fn($t) => $this->resumen($t, $idUsuario, $mios), $this->torneos->listar()),
            'puedoCrear' => $this->torneos->activosCreadosPor($idUsuario) === 0]);
    }

    public function detalle(int $idUsuario, int $idTorneo): array
    {
        $this->avanzar($idTorneo);
        $t = $this->torneos->torneo($idTorneo);
        if ($t === null) {
            return self::error('Ese torneo no existe.');
        }
        $ins = $this->torneos->inscriptos($idTorneo);
        $estoy = in_array($idUsuario, array_map('intval', array_column($ins, 'IDUsuario')), true);
        $rondas = [];
        $miPartida = null;
        foreach ($this->torneos->llaves($idTorneo) as $l) {
            $cruce = [
                'a' => $l['NombreA'], 'b' => $l['NombreB'], 'idA' => (int) $l['IDJugadorA'], 'idB' => $l['IDJugadorB'] === null ? null : (int) $l['IDJugadorB'],
                'ganador' => $l['IDGanador'] === null ? null : (int) $l['IDGanador'], 'resultado' => $l['Resultado'],
                'idPartida' => $l['IDPartida'] === null ? null : (int) $l['IDPartida'], 'enJuego' => $l['EstadoPartida'] === 'en_curso',
            ];
            $rondas[(int) $l['Ronda']][] = $cruce;
            if ($cruce['enJuego'] && $cruce['ganador'] === null && in_array($idUsuario, [$cruce['idA'], $cruce['idB']], true)) {
                $miPartida = $cruce['idPartida'];
            }
        }
        $total = $t['Estado'] === 'inscripcion' ? 0 : (int) log(self::tamanoCuadro(count($ins)), 2);
        return self::ok([
            'torneo' => $this->resumen($t, $idUsuario, $estoy ? [$idTorneo] : []) + ['totalRondas' => $total, 'soyCreador' => (int) $t['IDCreador'] === $idUsuario],
            'jugadores' => array_map(fn($j) => ['id' => (int) $j['IDUsuario'], 'nombre' => $j['NombreUsuario'], 'mmr' => (int) $j['MMR'],
                'semilla' => $j['Semilla'] === null ? null : (int) $j['Semilla'], 'eliminado' => (bool) $j['Eliminado']], $ins),
            'rondas' => array_values($rondas),
            'miPartida' => $miPartida,
        ]);
    }

    /** Para el HUD de Berlín: avanza mis torneos y dice si tengo una partida de torneo en juego. */
    public function miPartidaPendiente(int $idUsuario): ?array
    {
        foreach ($this->torneos->torneosActivosDe($idUsuario) as $idTorneo) {
            $this->avanzar($idTorneo);
            $t = $this->torneos->torneo($idTorneo);
            foreach ($this->torneos->llaves($idTorneo) as $l) {
                if ($l['EstadoPartida'] === 'en_curso' && $l['IDGanador'] === null
                    && in_array($idUsuario, [(int) $l['IDJugadorA'], (int) $l['IDJugadorB']], true)) {
                    return ['idTorneo' => $idTorneo, 'idPartida' => (int) $l['IDPartida'], 'torneo' => $t['Nombre'], 'ronda' => (int) $l['Ronda']];
                }
            }
        }
        return null;
    }

    public function torneoDePartida(int $idPartida): ?int
    {
        return $this->torneos->torneoDePartida($idPartida);
    }

    public function ganados(int $idUsuario): int
    {
        return $this->torneos->ganados($idUsuario);
    }
}
?>
