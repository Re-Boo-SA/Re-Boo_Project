<?php
require_once(__DIR__ . '/IMisionLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/NpcLogica.php');
require_once(__DIR__ . '/PartidaLogica.php');
require_once(__DIR__ . '/CampanaLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * ======================================================================
 *  MISIONES, EXPERIENCIA Y LOGROS (Fase 2)
 *
 *  El contenido (contactos, misiones, logros) está en datos/mundo/misiones.json.
 *  Acá están las reglas, y TODO se valida en el servidor:
 *   - para hablar o aceptar hay que estar cerca del contacto (posición guardada);
 *   - un paso "ir" se cumple solo si el servidor te ve dentro de la zona;
 *   - un paso "partida" o "ganar" cuenta partidas terminadas después de empezar ese paso;
 *   - la recompensa se cobra una sola vez (transacción + completar solo si estaba activa).
 *  Regla de oro: las recompensas son XP y puntos de tienda, nunca ventajas en las partidas.
 * ======================================================================
 */
class MisionLogica implements IMisionLogica
{
    public const DISTANCIA_HABLAR = 90;

    private $misiones;
    private $mundo;

    public function __construct()
    {
        $fachada = new FachadaPersistencia();
        $this->misiones = $fachada->retornoIPersistenciaMisiones();
        $this->mundo = $fachada->retornoIPersistenciaMundo();
    }

    /* ---------- datos ---------- */

    private static ?array $datos = null;

    public static function datos(): array
    {
        if (self::$datos === null) {
            $ruta = dirname(__DIR__) . '/datos/mundo/misiones.json';
            $json = is_file($ruta) ? json_decode((string) file_get_contents($ruta), true) : null;
            if (!is_array($json)) {
                throw new RuntimeException('Falta o está mal escrito datos/mundo/misiones.json.');
            }
            $json['porContacto'] = array_column($json['contactos'], null, 'id');
            $json['porMision'] = array_column($json['misiones'], null, 'id');
            self::$datos = $json;
        }
        return self::$datos;
    }

    /** Nivel según la experiencia: se llega al nivel n+1 con 50·n·(n+1) XP (100, 300, 600, 1000...). */
    public static function nivel(int $xp): int
    {
        $n = 1;
        while ($xp >= 50 * $n * ($n + 1)) {
            $n++;
        }
        return $n;
    }

    public static function xpParaNivel(int $nivel): int
    {
        return 50 * ($nivel - 1) * $nivel;
    }

    /* ---------- reglas ---------- */

    private function cerca(int $idUsuario, int $x, int $y, int $radio): bool
    {
        $p = $this->mundo->posicion($idUsuario);
        return $p !== null && hypot($p['X'] - $x, $p['Y'] - $y) <= $radio;
    }

    private function cercaDeContacto(int $idUsuario, string $idContacto): bool
    {
        if (!isset(self::datos()['porContacto'][$idContacto])) {
            return false;
        }
        $p = NpcLogica::posicion($idContacto, NpcLogica::horaActual());   // dónde está AHORA (rutina)
        return $p['estado'] !== 'ausente' && $this->cerca($idUsuario, $p['x'], $p['y'], self::DISTANCIA_HABLAR);
    }

    /** Si el contacto se fue, el mensaje dice a qué hora vuelve. */
    private function ausente(string $idContacto): ?string
    {
        $p = NpcLogica::posicion($idContacto, NpcLogica::horaActual());
        if ($p['estado'] !== 'ausente') {
            return null;
        }
        $c = self::datos()['porContacto'][$idContacto];
        $v = $p['vuelve'];
        return $c['nombre'] . ' no está: ' . $p['actividad'] . ($v === null ? '.' : '. Vuelve a las ' . sprintf('%02d:%02d', (int) $v, (int) round(fmod($v, 1) * 60)) . '.');
    }

    private function disponible(array $mision, array $progreso, int $nivel): bool
    {
        if (isset($progreso[$mision['id']])) {
            return false;
        }
        foreach ($mision['requisitos']['misiones'] ?? [] as $previa) {
            if (($progreso[$previa]['estado'] ?? '') !== 'completada') {
                return false;
            }
        }
        if ($nivel < ($mision['requisitos']['nivel'] ?? 1)) {
            return false;
        }
        foreach ($mision['requisitos']['confianza'] ?? [] as $contacto => $minimo) {
            if ($this->idUsuarioActual !== null && (new NpcLogica())->confianza($this->idUsuarioActual, $contacto) < $minimo) {
                return false;
            }
        }
        return true;
    }

    /** Para evaluar requisitos de confianza dentro de disponible(). */
    private ?int $idUsuarioActual = null;

    /**
     * Avanza los pasos que el servidor puede comprobar solo (ir, partida, ganar).
     * Los pasos "hablar" se cumplen con hablar(). Devuelve los avisos para el jugador.
     */
    private function evaluar(int $idUsuario): array
    {
        $avisos = [];
        foreach ($this->misiones->misionesDe($idUsuario) as $id => $p) {
            $mision = self::datos()['porMision'][$id] ?? null;
            if ($p['estado'] !== 'activa' || $mision === null) {
                continue;
            }
            $paso = $p['paso'];
            $fechaPaso = $p['fechaPaso'];
            while ($paso < count($mision['pasos'])) {
                $o = $mision['pasos'][$paso];
                $cumplido = match ($o['tipo']) {
                    'ir' => $this->cerca($idUsuario, $o['x'], $o['y'], $o['radio']),
                    'partida' => $this->misiones->partidasDesde($idUsuario, $fechaPaso, false, $o['lado'] ?? null) >= $o['cantidad'],
                    'ganar' => $this->misiones->partidasDesde($idUsuario, $fechaPaso, true, $o['lado'] ?? null) >= $o['cantidad'],
                    'desafio' => $this->desafioGanado($idUsuario, $id, $paso, $o),
                    default => false,
                };
                if (!$cumplido) {
                    break;
                }
                $paso++;
                $avisos[] = 'Objetivo cumplido: ' . $mision['titulo'];
                if ($paso >= count($mision['pasos'])) {
                    $avisos = array_merge($avisos, $this->completar($idUsuario, $mision)['avisos']);
                } else {
                    // El paso siguiente se evalúa en la próxima consulta, con la fecha que guardó MySQL
                    // (nunca se mezclan los relojes de PHP y de MySQL).
                    $this->misiones->avanzar($idUsuario, $id, $paso);
                }
                break;
            }
        }
        return $avisos;
    }

    /** Cierra la misión, da la recompensa (una sola vez) y revisa logros. */
    private function completar(int $idUsuario, array $mision): array
    {
        $avisos = [];
        $this->misiones->iniciarTransaccion();
        try {
            if (!$this->misiones->completar($idUsuario, $mision['id'])) {
                $this->misiones->revertir();
                return ['avisos' => []];
            }
            $r = $mision['recompensa'];
            $nivelAntes = self::nivel($this->misiones->xpDe($idUsuario));
            $this->misiones->sumarRecompensa($idUsuario, (int) $r['xp'], (int) $r['puntos']);
            if (!empty($r['confianza'])) {
                (new NpcLogica())->cambiarConfianza($idUsuario, $mision['contacto'], (int) $r['confianza']);
            }
            $nivelDespues = self::nivel($this->misiones->xpDe($idUsuario));
            $avisos[] = 'Misión completada: ' . $mision['titulo'] . ' (+' . $r['xp'] . ' XP, +' . $r['puntos'] . ' puntos de tienda)';
            if ($nivelDespues > $nivelAntes) {
                $avisos[] = '¡Subiste al nivel ' . $nivelDespues . '!';
            }
            foreach ($this->logrosNuevos($idUsuario, $nivelDespues) as $logro) {
                $avisos[] = 'Logro: ' . $logro['nombre'];
            }
            $this->misiones->confirmar();
        } catch (Throwable $e) {
            $this->misiones->revertir();
            throw $e;
        }
        (new AuditoriaLogica())->registrar('mision_completada', 'info', $idUsuario, null, $mision['id']);
        return ['avisos' => $avisos];
    }

    private function logrosNuevos(int $idUsuario, int $nivel): array
    {
        $progreso = $this->misiones->misionesDe($idUsuario);
        $completadas = array_keys(array_filter($progreso, fn($p) => $p['estado'] === 'completada'));
        $contactosConMision = [];
        foreach ($completadas as $id) {
            $contactosConMision[self::datos()['porMision'][$id]['contacto'] ?? ''] = true;
        }
        $nuevos = [];
        foreach (self::datos()['logros'] as $logro) {
            $c = $logro['condicion'];
            $cumple = (isset($c['misiones']) && count($completadas) >= $c['misiones'])
                || (isset($c['mision']) && in_array($c['mision'], $completadas, true))
                || (!empty($c['todos_los_contactos']) && count(array_filter(array_keys($contactosConMision))) >= count(self::datos()['contactos']))
                || (isset($c['nivel']) && $nivel >= $c['nivel']);
            if ($cumple && $this->misiones->otorgarLogro($idUsuario, $logro['id'])) {
                $nuevos[] = $logro;
            }
        }
        return $nuevos;
    }

    /* ---------- lo que pide el navegador ---------- */

    public function estado(int $idUsuario): array
    {
        $this->idUsuarioActual = $idUsuario;
        $avisos = $this->evaluar($idUsuario);
        $hora = NpcLogica::horaActual();
        $npc = new NpcLogica();
        $campana = (new CampanaLogica())->resumen($idUsuario);
        $prologoPendiente = ($campana[0]['estado'] ?? null) !== 'completado';
        $progreso = $this->misiones->misionesDe($idUsuario);
        $xp = $this->misiones->xpDe($idUsuario);
        $nivel = self::nivel($xp);
        $logros = $this->misiones->logrosDe($idUsuario);

        $contactos = [];
        foreach (self::datos()['contactos'] as $c) {
            $marca = null;
            foreach (self::datos()['misiones'] as $m) {
                $p = $progreso[$m['id']] ?? null;
                if ($p !== null && $p['estado'] === 'activa') {
                    $o = $m['pasos'][$p['paso']] ?? null;
                    if ($o !== null && in_array($o['tipo'], ['hablar', 'desafio'], true) && $o['contacto'] === $c['id']) {
                        $marca = '?';   // hay que volver a hablar con este contacto
                    }
                } elseif ($marca === null && $m['contacto'] === $c['id'] && $this->disponible($m, $progreso, $nivel)) {
                    $marca = '!';       // tiene una misión para ofrecerte
                }
            }
            if ($marca === null && $c['id'] === 'periodista' && $prologoPendiente) {
                $marca = '★';   // Ingrid tiene la caja de Viktor (campaña)
            }
            $pos = NpcLogica::posicion($c['id'], $hora);
            $contactos[] = ['id' => $c['id'], 'nombre' => $c['nombre'], 'rol' => $c['rol'], 'x' => $pos['x'], 'y' => $pos['y'],
                'variante' => $c['variante'], 'dir' => $c['dir'], 'marca' => $marca, 'lado' => $c['lado'] ?? null,
                'estado' => $pos['estado'], 'actividad' => $pos['actividad'], 'vuelve' => $pos['vuelve'],
                'rutina' => NpcLogica::rutina($c['id']), 'animo' => NpcLogica::animo($c['id'])['etiqueta'],
                'confianza' => $npc->confianza($idUsuario, $c['id'])];
        }

        $activas = [];
        foreach (self::datos()['misiones'] as $m) {
            $p = $progreso[$m['id']] ?? null;
            if ($p === null || $p['estado'] !== 'activa') {
                continue;
            }
            $o = $m['pasos'][$p['paso']];
            $activas[] = [
                'id' => $m['id'], 'titulo' => $m['titulo'], 'paso' => $p['paso'] + 1, 'pasos' => count($m['pasos']), 'texto' => $o['texto'],
                'objetivo' => $o['tipo'] === 'ir' ? ['x' => $o['x'], 'y' => $o['y'], 'radio' => $o['radio'], 'lugar' => $o['lugar']]
                    : (in_array($o['tipo'], ['hablar', 'desafio'], true) ? ['contacto' => $o['contacto'], 'radio' => 60, 'lugar' => self::datos()['porContacto'][$o['contacto']]['nombre']]
                        + array_intersect_key(NpcLogica::posicion($o['contacto'], $hora), ['x' => 1, 'y' => 1]) : null),
            ];
        }

        return [
            'campana' => $campana,
            'xp' => $xp, 'nivel' => $nivel, 'xpDesde' => self::xpParaNivel($nivel), 'xpHasta' => self::xpParaNivel($nivel + 1),
            'contactos' => $contactos, 'activas' => $activas,
            'completadas' => count(array_filter($progreso, fn($p) => $p['estado'] === 'completada')),
            'logros' => array_map(fn($l) => ['id' => $l['id'], 'nombre' => $l['nombre'], 'descripcion' => $l['descripcion'],
                'obtenido' => isset($logros[$l['id']])], self::datos()['logros']),
            'distanciaHablar' => self::DISTANCIA_HABLAR,
            'avisos' => $avisos,
        ];
    }

    /** Hablar con un contacto: avanza un paso "hablar", ofrece una misión o simplemente saluda. */
    public function hablar(int $idUsuario, string $idContacto): array
    {
        $contacto = self::datos()['porContacto'][$idContacto] ?? null;
        if ($contacto === null) {
            return ['exito' => false, 'mensaje' => 'Esa persona no existe.'];
        }
        if (($fuera = $this->ausente($idContacto)) !== null) {
            return ['exito' => false, 'mensaje' => $fuera];
        }
        if (!$this->cercaDeContacto($idUsuario, $idContacto)) {
            return ['exito' => false, 'mensaje' => 'Acercate más para hablar.'];
        }
        $this->idUsuarioActual = $idUsuario;
        $avisos = $this->evaluar($idUsuario);
        $progreso = $this->misiones->misionesDe($idUsuario);
        $npc = new NpcLogica();
        $animo = NpcLogica::animo($idContacto);
        $respuesta = ['exito' => true, 'contacto' => ['id' => $contacto['id'], 'nombre' => $contacto['nombre'], 'rol' => $contacto['rol'],
            'animo' => $animo['etiqueta'], 'emocion' => $animo['emocion'], 'confianza' => $npc->confianza($idUsuario, $idContacto)]];

        // 1) ¿Hay una misión activa esperando hablar con esta persona?
        foreach (self::datos()['misiones'] as $m) {
            $p = $progreso[$m['id']] ?? null;
            if ($p === null || $p['estado'] !== 'activa') {
                continue;
            }
            $o = $m['pasos'][$p['paso']];
            if ($o['tipo'] === 'desafio' && $o['contacto'] === $idContacto) {
                return $respuesta + $this->presentarDesafio($m, $p['paso'], $o, $idUsuario) + ['avisos' => $avisos];
            }
            if ($o['tipo'] === 'hablar' && $o['contacto'] === $idContacto) {
                $siguiente = $p['paso'] + 1;
                $texto = $o['dialogo'];
                if ($siguiente >= count($m['pasos'])) {
                    $avisos = array_merge($avisos, $this->completar($idUsuario, $m)['avisos']);
                    $partes = [$o['dialogo'], $m['final']];   // por separado, para poder traducirlas
                    $texto .= ' ' . $m['final'];
                } else {
                    $this->misiones->avanzar($idUsuario, $m['id'], $siguiente);
                    $avisos[] = 'Objetivo cumplido: ' . $m['titulo'];
                }
                return $respuesta + ['texto' => $texto, 'partes' => $partes ?? [$texto], 'avisos' => $avisos];
            }
        }
        // 2) ¿Tiene una misión para ofrecerte?
        $nivel = self::nivel($this->misiones->xpDe($idUsuario));
        foreach (self::datos()['misiones'] as $m) {
            if ($m['contacto'] === $idContacto && $this->disponible($m, $progreso, $nivel)) {
                return $respuesta + ['texto' => $m['oferta'], 'oferta' => ['id' => $m['id'], 'titulo' => $m['titulo'],
                    'xp' => $m['recompensa']['xp'], 'puntos' => $m['recompensa']['puntos']], 'avisos' => $avisos];
            }
        }
        // 3) Nada pendiente de misiones: charla libre (árbol de diálogo con opciones)
        return $respuesta + ['dialogo' => $npc->empezar($idUsuario, $idContacto), 'avisos' => $avisos];
    }

    /* ---------- desafíos: partidas contra el bot de un contacto (etapa C) ---------- */

    /** Resultado de un intento: null si la partida sigue; ganada, perdida, sin_secundario o abandonada si terminó. */
    private function resultadoIntento(array $o, int $idPartida, int $idUsuario): ?string
    {
        $r = $this->misiones->resultadoDesafio($idPartida, $idUsuario);
        if ($r === null || in_array($r['EstadoPartida'], ['esperando', 'en_curso'], true)) {
            return null;
        }
        if ($r['EstadoPartida'] === 'cancelada' || $r['EstadoJugador'] !== 'activo') {
            return 'abandonada';
        }
        $gano = ($o['objetivo'] ?? 'ganar') === 'terminar' || (int) $r['Posicion'] === 1;
        if (!$gano) {
            return 'perdida';
        }
        return $this->cumpleSecundario($o['secundario'] ?? null, $idPartida, $idUsuario, (int) $r['PuntajeFinal']) ? 'ganada' : 'sin_secundario';
    }

    /** Objetivos secundarios: fichas mínimas en un recinto, no usar el Muro (o un máximo) y puntaje mínimo. */
    private function cumpleSecundario(?array $s, int $idPartida, int $idUsuario, int $puntaje): bool
    {
        if ($s === null) {
            return true;
        }
        $enRecinto = fn(string $r) => count(array_filter($this->misiones->tableroFinal($idPartida, $idUsuario), fn($f) => $f['recinto'] === $r));
        return match ($s['tipo']) {
            'recinto_min' => $enRecinto($s['recinto']) >= $s['cantidad'],
            'muro_max' => $enRecinto('muro') <= $s['cantidad'],
            'puntos_min' => $puntaje >= $s['puntos'],
            default => false,
        };
    }

    private function desafioGanado(int $idUsuario, string $idMision, int $paso, array $o): bool
    {
        foreach ($this->misiones->intentos($idUsuario, $idMision, $paso) as $i) {
            $r = $i['Resultado'] ?? $this->resultadoIntento($o, (int) $i['IDPartida'], $idUsuario);
            if ($i['Resultado'] === null && $r !== null) {
                $this->misiones->marcarIntento((int) $i['IDPartida'], $r);
            }
            if ($r === 'ganada') {
                return true;
            }
        }
        return false;
    }

    /** Lo que dice el contacto cuando te toca su desafío: invitación, o un comentario sobre tu último intento. */
    private function presentarDesafio(array $m, int $paso, array $o, int $idUsuario): array
    {
        $intentos = $this->misiones->intentos($idUsuario, $m['id'], $paso);
        $ultimo = $intentos[0] ?? null;
        $enJuego = $ultimo !== null && in_array($ultimo['Estado'], ['esperando', 'en_curso'], true) ? (int) $ultimo['IDPartida'] : null;
        $resultado = $ultimo === null || $enJuego !== null ? null : ($ultimo['Resultado'] ?? $this->resultadoIntento($o, (int) $ultimo['IDPartida'], $idUsuario));
        $texto = match ($resultado) {
            'perdida', 'abandonada' => $o['perdiste'],
            'sin_secundario' => $o['faltaSecundario'] ?: $o['perdiste'],
            default => $o['invitacion'],
        };
        return ['texto' => $texto, 'desafio' => ['mision' => $m['id'], 'titulo' => $m['titulo'], 'reto' => $o['texto'],
            'secundario' => $o['secundario']['texto'] ?? null, 'enJuego' => $enJuego, 'intentos' => count($intentos), 'ultimo' => $resultado]];
    }

    /** Empezar (o retomar) la partida del desafío. Hay que estar cerca del contacto y sin otra partida en juego. */
    public function desafiar(int $idUsuario, string $idMision): array
    {
        $m = self::datos()['porMision'][$idMision] ?? null;
        $p = $m === null ? null : ($this->misiones->misionesDe($idUsuario)[$idMision] ?? null);
        $o = $p === null || $p['estado'] !== 'activa' ? null : $m['pasos'][$p['paso']];
        if ($o === null || $o['tipo'] !== 'desafio') {
            return ['exito' => false, 'mensaje' => 'Ahora no tenés ese desafío pendiente.'];
        }
        if (($fuera = $this->ausente($o['contacto'])) !== null) {
            return ['exito' => false, 'mensaje' => $fuera];
        }
        if (!$this->cercaDeContacto($idUsuario, $o['contacto'])) {
            return ['exito' => false, 'mensaje' => 'Acercate más para hablar.'];
        }
        $partidas = new PartidaLogica();
        $actual = $partidas->partidaActiva($idUsuario);
        if ($actual !== null) {
            $mio = array_filter($this->misiones->intentos($idUsuario, $idMision, $p['paso']), fn($i) => (int) $i['IDPartida'] === $actual);
            return $mio !== [] ? ['exito' => true, 'idPartida' => $actual] : ['exito' => false, 'mensaje' => 'Terminá la partida en la que estás.'];
        }
        $config = ConfigPartida::desdeArreglo(($o['reglas'] ?? []) + ['permiteBots' => 1]);
        if (($error = $config->validar()) !== null) {
            return ['exito' => false, 'mensaje' => $error];
        }
        $this->misiones->iniciarTransaccion();
        try {
            $id = $partidas->crearDesafio($idUsuario, (int) self::datos()['porContacto'][$o['contacto']]['bot'], $config, 'Desafío: ' . $m['titulo'], $o['escenario'] ?? []);
            $this->misiones->registrarIntento($id, $idUsuario, $idMision, $p['paso']);
            $this->misiones->confirmar();
        } catch (Throwable $e) {
            $this->misiones->revertir();
            throw $e;
        }
        return ['exito' => true, 'idPartida' => $id];
    }

    /** Adónde se vuelve al terminar un desafío: a Berlín, o al capítulo de la campaña si la partida es de la historia. */
    public function capituloDeDesafio(int $idPartida): ?string
    {
        $m = $this->misiones->misionDePartida($idPartida);
        return $m !== null && str_starts_with($m, 'campana:') ? explode(':', $m)[1] : null;
    }

    public function esDesafio(int $idPartida): bool
    {
        return $this->misiones->esDesafio($idPartida);
    }

    /** Elegir una respuesta en una charla (o empezarla con $idNodo vacío). Hay que seguir cerca del contacto. */
    public function charlar(int $idUsuario, string $idContacto, string $idNodo, int $opcion): array
    {
        if (!isset(self::datos()['porContacto'][$idContacto]) || !isset(NpcLogica::dialogos()[$idContacto])) {
            return ['exito' => false, 'mensaje' => 'Esa persona no existe.'];
        }
        if (($fuera = $this->ausente($idContacto)) !== null) {
            return ['exito' => false, 'mensaje' => $fuera];
        }
        if (!$this->cercaDeContacto($idUsuario, $idContacto)) {
            return ['exito' => false, 'mensaje' => 'Acercate más para hablar.'];
        }
        $npc = new NpcLogica();
        return $idNodo === '' ? ['exito' => true, 'dialogo' => $npc->empezar($idUsuario, $idContacto)] : $npc->responder($idUsuario, $idContacto, $idNodo, $opcion);
    }

    public function aceptar(int $idUsuario, string $idMision): array
    {
        $this->idUsuarioActual = $idUsuario;
        $m = self::datos()['porMision'][$idMision] ?? null;
        if ($m === null) {
            return ['exito' => false, 'mensaje' => 'Esa misión no existe.'];
        }
        if (!$this->cercaDeContacto($idUsuario, $m['contacto'])) {
            return ['exito' => false, 'mensaje' => 'Acercate más para hablar.'];
        }
        $nivel = self::nivel($this->misiones->xpDe($idUsuario));
        if (!$this->disponible($m, $this->misiones->misionesDe($idUsuario), $nivel)) {
            return ['exito' => false, 'mensaje' => 'Esa misión no está disponible para vos ahora.'];
        }
        if (!$this->misiones->iniciar($idUsuario, $idMision)) {
            return ['exito' => false, 'mensaje' => 'Ya aceptaste esa misión.'];
        }
        $avisos = $this->evaluar($idUsuario);   // por si ya estás parado en el primer objetivo
        return ['exito' => true, 'mensaje' => 'Misión aceptada: ' . $m['titulo'], 'avisos' => $avisos];
    }
}
?>
