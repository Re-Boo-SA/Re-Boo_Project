<?php
require_once(__DIR__ . '/IMundoLogica.php');
require_once(__DIR__ . '/PartidaLogica.php');
require_once(__DIR__ . '/SeguridadLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/MisionLogica.php');
require_once(__DIR__ . '/SocialLogica.php');
require_once(__DIR__ . '/SucesoLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * ======================================================================
 *  MUNDO ABIERTO: BERLÍN 1961-1989
 *
 *  Un mapa compartido de 2400 × 1400 unidades. El Muro corre de norte a
 *  sur en x = 1200 y solo se cruza por Checkpoint Charlie. Cada lado
 *  tiene 6 distritos; cada distrito, un café donde se arman partidas.
 *
 *  El servidor valida cada movimiento (no se puede atravesar el Muro ni
 *  teletransportarse) y lleva la influencia de cada bando por distrito
 *  durante temporadas de 30 días.
 * ======================================================================
 */
class MundoLogica implements IMundoLogica
{
    public const VELOCIDAD = 260;              // unidades por segundo
    public const SEGUNDOS_ACTIVO = 15;         // tras esto, el personaje desaparece del mapa
    public const DISTANCIA_PUERTA = 120;       // qué tan cerca hay que estar para entrar (el cliente)
    public const BONO_DOMINIO = 0.10;
    public const BANDOS = ['oeste', 'este'];
    /* Mundo vivo (Fase 1): la hora y el clima salen del reloj del servidor, así todos ven lo mismo. */
    public const SEGUNDOS_POR_DIA = 1800;      // un día de juego = 30 minutos reales
    public const SEGUNDOS_POR_CLIMA = 600;     // el clima cambia cada 10 minutos
    /** Clima => probabilidad en %. */
    public const CLIMAS = ['despejado' => 45, 'nublado' => 20, 'lluvia' => 17, 'niebla' => 11, 'nieve' => 7];
    /** Opciones de apariencia: el índice del sprite es piel * 12 + pelo * 3 + gorro. */
    public const PIELES = 3;
    public const PELOS = 4;
    public const GORROS = 3;

    /** Cuerpo del personaje respecto de sus pies (x, y): se usa igual en el navegador y acá. */
    public const CUERPO = ['izq' => -10, 'der' => 9, 'arriba' => -10, 'abajo' => -1];

    private $mundo;

    public function __construct()
    {
        $this->mundo = (new FachadaPersistencia())->retornoIPersistenciaMundo();
    }

    /* ---------- El mapa (un solo archivo: datos/mundo/berlin.json, formato Tiled) ---------- */

    private static ?array $mapaCargado = null;

    /**
     * Lee el mapa una vez por pedido. Es la ÚNICA fuente de verdad: el navegador dibuja
     * con este mismo archivo y el servidor valida los pasos con su capa de colisión.
     */
    public static function mapaTiled(): array
    {
        if (self::$mapaCargado === null) {
            $ruta = dirname(__DIR__) . '/datos/mundo/berlin.json';
            $json = is_file($ruta) ? json_decode((string) file_get_contents($ruta), true) : null;
            if (!is_array($json)) {
                throw new RuntimeException('Falta datos/mundo/berlin.json (corré herramientas/arte/generar_mundo.py).');
            }
            $colision = [];
            $objetos = [];
            foreach ($json['layers'] as $capa) {
                if ($capa['name'] === 'colision') {
                    $colision = $capa['data'];
                } elseif ($capa['name'] === 'objetos') {
                    $objetos = $capa['objects'];
                }
            }
            $edificios = [];
            $spawn = [];
            foreach ($objetos as $o) {
                if ($o['type'] === 'spawn') {
                    $spawn[str_replace('spawn_', '', $o['name'])] = [(int) $o['x'], (int) $o['y']];
                    continue;
                }
                if ($o['type'] !== 'edificio') {
                    continue;   // rutas de NPCs y guardias: solo las usa el navegador
                }
                $p = [];
                foreach ($o['properties'] ?? [] as $prop) {
                    $p[$prop['name']] = $prop['value'];
                }
                $edificios[] = [
                    'id' => $p['id'], 'nombre' => $p['nombre'], 'tipo' => $p['tipo'], 'distrito' => $p['distrito'] ?? null,
                    'x' => (int) $o['x'], 'y' => (int) $o['y'], 'w' => (int) $o['width'], 'h' => (int) $o['height'],
                    'puertaX' => (int) $p['puertaX'], 'puertaY' => (int) $p['puertaY'],
                    'luces' => json_decode($p['luces'] ?? '[]', true) ?: [],
                ];
            }
            self::$mapaCargado = [
                'json' => $json, 'tile' => (int) $json['tilewidth'], 'cols' => (int) $json['width'], 'filas' => (int) $json['height'],
                'ancho' => (int) $json['width'] * (int) $json['tilewidth'], 'alto' => (int) $json['height'] * (int) $json['tileheight'],
                'colision' => $colision, 'edificios' => $edificios, 'spawn' => $spawn,
            ];
        }
        return self::$mapaCargado;
    }

    /** Todo lo que el navegador necesita para dibujar el mundo (se pide una vez al entrar). */
    public function mapa(): array
    {
        $m = self::mapaTiled();
        return [
            'tiled' => $m['json'], 'ancho' => $m['ancho'], 'alto' => $m['alto'], 'tile' => $m['tile'],
            'apariencias' => ['pieles' => self::PIELES, 'pelos' => self::PELOS, 'gorros' => self::GORROS],
            'velocidad' => self::VELOCIDAD, 'distanciaPuerta' => self::DISTANCIA_PUERTA, 'cuerpo' => self::CUERPO,
            'edificios' => $m['edificios'],
            'distritos' => array_map(fn($d) => [
                'id' => (int) $d['IDDistrito'], 'nombre' => $d['Nombre'], 'lado' => $d['Lado'],
                'x' => (int) $d['X'], 'y' => (int) $d['Y'], 'w' => (int) $d['Ancho'], 'h' => (int) $d['Alto'],
            ], $this->mundo->distritos()),
        ];
    }

    /* ---------- Reloj y clima (deterministas: no se guardan) ---------- */

    /** El clima de un tramo de 10 minutos. Siempre da lo mismo para el mismo tramo. */
    public static function climaDeTramo(int $tramo): string
    {
        $tirada = crc32('clima-berlin-' . $tramo) % 100;
        $acumulado = 0;
        foreach (self::CLIMAS as $clima => $porcentaje) {
            $acumulado += $porcentaje;
            if ($tirada < $acumulado) {
                return $clima;
            }
        }
        return 'despejado';
    }

    /** Solo para pruebas automáticas: fijan la hora (0-24) y el clima. En el juego quedan en null. */
    public static ?float $horaFija = null;
    public static ?string $climaFijo = null;

    /** Hora del juego (0 a 24) y clima en el instante $t (segundos Unix). */
    public static function reloj(float $t): array
    {
        $tramo = intdiv((int) $t, self::SEGUNDOS_POR_CLIMA);
        $r = [
            'ahora' => round($t, 3),
            'hora' => round(fmod($t, self::SEGUNDOS_POR_DIA) / self::SEGUNDOS_POR_DIA * 24, 4),
            'segundosPorDia' => self::SEGUNDOS_POR_DIA,
            'clima' => self::climaDeTramo($tramo),
            'climaSiguiente' => self::climaDeTramo($tramo + 1),
            'segundosClima' => self::SEGUNDOS_POR_CLIMA - ((int) $t % self::SEGUNDOS_POR_CLIMA),
        ];
        if (self::$horaFija !== null) {
            $r['hora'] = self::$horaFija;
        }
        if (self::$climaFijo !== null) {
            $r['clima'] = self::$climaFijo;
        }
        return $r;
    }

    public static function indiceApariencia(int $piel, int $pelo, int $gorro): int
    {
        return $piel * self::PELOS * self::GORROS + $pelo * self::GORROS + $gorro;
    }

    public static function distritoEn(int $x, int $y, array $distritos): ?int
    {
        foreach ($distritos as $d) {
            if ($x >= $d['X'] && $x < $d['X'] + $d['Ancho'] && $y >= $d['Y'] && $y < $d['Y'] + $d['Alto']) {
                return (int) $d['IDDistrito'];
            }
        }
        return null;
    }

    /** ¿Puede estar parado el personaje con los pies en (x, y)? Revisa las cuatro esquinas de su cuerpo. */
    public static function libre(int $x, int $y): bool
    {
        $m = self::mapaTiled();
        foreach ([[self::CUERPO['izq'], self::CUERPO['arriba']], [self::CUERPO['der'], self::CUERPO['arriba']],
                  [self::CUERPO['izq'], self::CUERPO['abajo']], [self::CUERPO['der'], self::CUERPO['abajo']]] as [$dx, $dy]) {
            $px = $x + $dx;
            $py = $y + $dy;
            if ($px < 0 || $py < 0 || $px >= $m['ancho'] || $py >= $m['alto']) {
                return false;
            }
            if ($m['colision'][intdiv($py, $m['tile']) * $m['cols'] + intdiv($px, $m['tile'])] !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * ¿Se puede ir de (x1,y1) a (x2,y2) en $segundos? Revisa velocidad y obstáculos a lo largo
     * de TODO el camino (no solo el destino), así no se atraviesan paredes ni el Muro:
     * al otro lado solo se llega por Checkpoint Charlie.
     * Pública y estática para probarla sin base de datos.
     */
    public static function movimientoValido(int $x1, int $y1, int $x2, int $y2, float $segundos): bool
    {
        // Con control sorpresa en Checkpoint Charlie (suceso), dentro del paso se avanza más lento.
        $maximo = self::VELOCIDAD * SucesoLogica::factorVelocidad($x1, $y1, $x2, $y2) * max($segundos, 0.25) * 1.6 + 40;
        $distancia = hypot($x2 - $x1, $y2 - $y1);
        if ($distancia > $maximo || !self::libre($x2, $y2)) {
            return false;
        }
        $pasos = (int) ceil($distancia / 8);
        for ($i = 1; $i < $pasos; $i++) {
            if (!self::libre((int) round($x1 + ($x2 - $x1) * $i / $pasos), (int) round($y1 + ($y2 - $y1) * $i / $pasos))) {
                return false;
            }
        }
        return true;
    }

    public static function puntoDeAparicion(?string $bando): array
    {
        $m = self::mapaTiled();
        return $m['spawn'][$bando === 'este' ? 'este' : 'oeste'];
    }

    /* ---------- Temporadas e influencia ---------- */

    /** Temporada vigente. Si la anterior venció, la cierra y abre la siguiente. */
    public function temporadaVigente(): array
    {
        $t = $this->mundo->ultimaTemporada();
        if ($t === null) {
            $this->mundo->crearTemporada(1);
            return $this->mundo->ultimaTemporada();
        }
        if ((int) $t['Cerrada'] === 0 && (int) $t['SegundosRestantes'] <= 0) {
            $dominio = $this->dominio((int) $t['IDTemporada']);
            $oeste = count(array_filter($dominio, fn($d) => $d['domina'] === 'oeste'));
            $este = count(array_filter($dominio, fn($d) => $d['domina'] === 'este'));
            $totalOeste = array_sum(array_column($dominio, 'oeste'));
            $totalEste = array_sum(array_column($dominio, 'este'));
            $ganador = $oeste !== $este ? ($oeste > $este ? 'oeste' : 'este')
                : ($totalOeste !== $totalEste ? ($totalOeste > $totalEste ? 'oeste' : 'este') : 'empate');
            $this->mundo->cerrarTemporada((int) $t['IDTemporada'], $ganador, $oeste, $este);
            $this->mundo->crearTemporada((int) $t['Numero'] + 1);
            $t = $this->mundo->ultimaTemporada();
        }
        return $t;
    }

    /** Puntos de cada bando por distrito y quién domina (más puntos; empate = nadie). */
    public function dominio(int $idTemporada): array
    {
        $resultado = [];
        foreach ($this->mundo->distritos() as $d) {
            $resultado[(int) $d['IDDistrito']] = ['oeste' => 0, 'este' => 0, 'domina' => null];
        }
        foreach ($this->mundo->influencia($idTemporada) as $fila) {
            $resultado[(int) $fila['IDDistrito']][$fila['Bando']] = (int) $fila['Puntos'];
        }
        foreach ($resultado as &$d) {
            $d['domina'] = $d['oeste'] === $d['este'] ? null : ($d['oeste'] > $d['este'] ? 'oeste' : 'este');
        }
        unset($d);
        return $resultado;
    }

    /**
     * Al terminar una partida (lo llama PartidaLogica dentro de su transacción):
     * cada humano con bando suma influencia en el distrito donde se jugó
     * (o, si la partida no salió de un café, donde estaba parado). 1.º = 3,
     * 2.º = 2, resto = 1; el competitivo vale doble. Si tu bando ya dominaba
     * ese distrito, ganás un 10 % más de puntos de tienda.
     */
    public function registrarPartida(int $idPartida, bool $competitiva): void
    {
        $temporada = $this->temporadaVigente();
        $idTemporada = (int) $temporada['IDTemporada'];
        $dominio = $this->dominio($idTemporada);
        $distritos = $this->mundo->distritos();
        $distritoPartida = $this->mundo->distritoDePartida($idPartida);

        foreach ($this->mundo->participantesParaInfluencia($idPartida) as $p) {
            if ((int) $p['EsBot'] === 1 || $p['Bando'] === null || $p['Estado'] !== 'activo') {
                continue;
            }
            $idDistrito = $distritoPartida
                ?? ($p['X'] === null ? null : self::distritoEn((int) $p['X'], (int) $p['Y'], $distritos));
            if ($idDistrito === null) {
                continue;
            }
            // Noche de torneo en ese café (suceso): la influencia se multiplica.
            $puntos = match ((int) $p['Posicion']) { 1 => 3, 2 => 2, default => 1 } * ($competitiva ? 2 : 1)
                * SucesoLogica::multiplicadorInfluencia($distritoPartida);
            if (($dominio[$idDistrito]['domina'] ?? null) === $p['Bando'] && (int) $p['PuntosOtorgados'] > 0) {
                $this->mundo->sumarBonoTienda($idPartida, (int) $p['IDUsuario'], (int) ceil($p['PuntosOtorgados'] * self::BONO_DOMINIO));
            }
            $this->mundo->sumarInfluencia($idTemporada, $idDistrito, $p['Bando'], $puntos);
        }
    }

    /* ---------- Acciones del jugador ---------- */

    public function elegirBando(int $idUsuario, string $bando): array
    {
        if (!in_array($bando, self::BANDOS, true)) {
            return ['exito' => false, 'mensaje' => 'Ese bando no existe.'];
        }
        $temporada = $this->temporadaVigente();
        $actual = $this->mundo->bandoDe($idUsuario);
        if ($actual !== null && $actual['Bando'] !== null && (int) $actual['IDTemporadaBando'] === (int) $temporada['IDTemporada']) {
            return ['exito' => false, 'mensaje' => 'Ya elegiste bando en esta temporada. Vas a poder cambiarlo en la próxima.'];
        }
        $this->mundo->elegirBando($idUsuario, $bando, (int) $temporada['IDTemporada']);
        [$x, $y] = self::puntoDeAparicion($bando);
        $this->mundo->crearPosicion($idUsuario, $x, $y, 0);
        return ['exito' => true];
    }

    /** Mueve al personaje si el movimiento es válido; si no, lo deja donde estaba. */
    public function mover(int $idUsuario, int $x, int $y, int $direccion): array
    {
        $anterior = $this->mundo->posicion($idUsuario);
        if ($anterior === null) {
            // Primera vez, o la tabla en memoria se vació: aparece en su lado del Muro.
            $bando = $this->mundo->bandoDe($idUsuario)['Bando'] ?? null;
            [$x, $y] = self::puntoDeAparicion($bando);
            $this->mundo->crearPosicion($idUsuario, $x, $y, 0);
            return ['x' => $x, 'y' => $y, 'corregido' => true];
        }
        $ok = self::movimientoValido((int) $anterior['X'], (int) $anterior['Y'], $x, $y, (float) $anterior['Segundos']);
        if (!$ok) {
            $x = (int) $anterior['X'];
            $y = (int) $anterior['Y'];
        }
        $this->mundo->guardarPosicion($idUsuario, $x, $y, $direccion & 3);
        if (random_int(1, 200) === 1) {
            $this->mundo->purgarPosiciones();
        }
        return ['x' => $x, 'y' => $y, 'corregido' => !$ok];
    }

    /** Lo que cambia varias veces por segundo: quién está dónde y los mensajes nuevos. */
    public function estadoLiviano(int $idUsuario, int $desdeMensaje = 0): array
    {
        // Con quien hay un bloqueo (en cualquier sentido) no nos vemos ni nos leemos.
        $ocultos = array_flip((new SocialLogica())->bloqueadosEntreAmbos($idUsuario));
        $jugadores = array_map(fn($j) => [
            'id' => (int) $j['IDUsuario'], 'nombre' => $j['NombreUsuario'], 'bando' => $j['Bando'],
            'x' => (int) $j['X'], 'y' => (int) $j['Y'], 'dir' => (int) $j['Direccion'],
            'rango' => RankingLogica::rango((int) $j['MMR'])['icono'], 'ap' => (int) $j['Apariencia'], 'per' => $j['Personal'] ?? null,
            'nivel' => MisionLogica::nivel((int) $j['XP']), 'gesto' => (int) $j['Gesto'],
        ], array_filter($this->mundo->jugadoresActivos(self::SEGUNDOS_ACTIVO), fn($j) => !isset($ocultos[(int) $j['IDUsuario']])));
        $jugadores = array_values($jugadores);
        $mensajes = array_map(fn($m) => ['id' => (int) $m['IDMensaje'], 'jugador' => (int) $m['IDUsuario'], 'texto' => $m['Texto']],
            array_filter($this->mundo->mensajesRecientes(8, $desdeMensaje), fn($m) => !isset($ocultos[(int) $m['IDUsuario']])));
        $mensajes = array_values($mensajes);
        return ['jugadores' => $jugadores, 'mensajes' => $mensajes];
    }

    /** Estado completo (al entrar y cada ~10 s): temporada, dominio de distritos y mi bando. */
    public function estado(int $idUsuario): array
    {
        $temporada = $this->temporadaVigente();
        $dominio = $this->dominio((int) $temporada['IDTemporada']);
        $bando = $this->mundo->bandoDe($idUsuario);
        $posicion = $this->mundo->posicion($idUsuario);
        if ($posicion !== null) {
            $this->mundo->refrescarPerfil($idUsuario);
        }
        $apariencia = $this->mundo->apariencia($idUsuario) ?? ['Piel' => 0, 'Pelo' => 0, 'Gorro' => 0];
        return [
            'reloj' => self::reloj(microtime(true)),
            'yo' => [
                'id' => $idUsuario,
                'bando' => $bando['Bando'] ?? null,
                'puedeCambiarBando' => ($bando['Bando'] ?? null) === null || (int) $bando['IDTemporadaBando'] !== (int) $temporada['IDTemporada'],
                'x' => $posicion ? (int) $posicion['X'] : null,
                'y' => $posicion ? (int) $posicion['Y'] : null,
                'personal' => $this->mundo->personaje($idUsuario),
                'apariencia' => ['piel' => $apariencia['Piel'], 'pelo' => $apariencia['Pelo'], 'gorro' => $apariencia['Gorro'],
                    'indice' => self::indiceApariencia($apariencia['Piel'], $apariencia['Pelo'], $apariencia['Gorro'])],
            ],
            'temporada' => ['numero' => (int) $temporada['Numero'], 'segundosRestantes' => max(0, (int) $temporada['SegundosRestantes'])],
            'dominio' => $dominio,
        ] + $this->estadoLiviano($idUsuario);
    }

    public const GESTOS = [1 => 'saludar', 2 => 'aplaudir', 3 => 'encogerse', 4 => 'sentarse'];

    public function gesto(int $idUsuario, int $gesto): array
    {
        if (!isset(self::GESTOS[$gesto])) {
            return ['exito' => false, 'mensaje' => 'Ese gesto no existe.'];
        }
        // Sentarse dura hasta que te movés (o 10 minutos); los demás, unos segundos.
        return ['exito' => $this->mundo->gesto($idUsuario, $gesto, $gesto === 4 ? 600 : 3)];
    }

    /**
     * Personaje personalizado: p<pelo>s<sombrero>a<abrigo>x<accesorios>-<7 colores hex>.
     * Pelo 0-4, sombrero 0-4, abrigo 0-2, accesorios = bits (1 anteojos, 2 bigote, 4 barba, 8 bufanda).
     * Colores: piel, pelo, abrigo, detalle, pantalón, sombrero, bufanda.
     */
    public const PERSONAJE = '/^p[0-4]s[0-4]a[0-2]x[0-9a-f]-[0-9a-f]{42}$/';

    public function guardarPersonaje(int $idUsuario, string $codigo): array
    {
        $codigo = strtolower(trim($codigo));
        if (!preg_match(self::PERSONAJE, $codigo)) {
            return ['exito' => false, 'mensaje' => 'Ese personaje no es válido.'];
        }
        $this->mundo->guardarPersonaje($idUsuario, $codigo);
        return ['exito' => true, 'personal' => $codigo];
    }

    public function guardarApariencia(int $idUsuario, int $piel, int $pelo, int $gorro): array
    {
        if ($piel < 0 || $piel >= self::PIELES || $pelo < 0 || $pelo >= self::PELOS || $gorro < 0 || $gorro >= self::GORROS) {
            return ['exito' => false, 'mensaje' => 'Esa apariencia no existe.'];
        }
        $this->mundo->guardarApariencia($idUsuario, $piel, $pelo, $gorro);
        $this->mundo->refrescarPerfil($idUsuario);
        return ['exito' => true, 'indice' => self::indiceApariencia($piel, $pelo, $gorro)];
    }

    public function decir(int $idUsuario, string $texto): array
    {
        $texto = SeguridadLogica::normalizarTexto($texto);
        if ($texto === '') {
            return ['exito' => false, 'mensaje' => 'El mensaje está vacío.'];
        }
        if (mb_strlen($texto) > 80) {
            $texto = mb_substr($texto, 0, 80);
        }
        $this->mundo->decir($idUsuario, $texto);
        if (random_int(1, 20) === 1) {
            $this->mundo->purgarMensajes();
        }
        return ['exito' => true];
    }

    public function reportar(int $idUsuario, int $idMensaje): array
    {
        $m = $this->mundo->mensaje($idMensaje);
        if ($m === null) {
            return ['exito' => false, 'mensaje' => 'Ese mensaje ya no existe.'];
        }
        (new FachadaPersistencia())->retornoIPersistenciaReporte()->crear(
            'chat',
            'Chat de Berlín · ' . $m['NombreUsuario'] . ': ' . $m['Texto'],
            null,
            $idUsuario,
            (int) $m['IDUsuario'],
            null
        );
        (new AuditoriaLogica())->registrar('reporte_chat', 'aviso', $idUsuario, null, 'Mensaje ' . $idMensaje);
        return ['exito' => true, 'mensaje' => 'Gracias: un administrador va a revisar el mensaje.'];
    }

    /* ---------- Cafés ---------- */

    private function cercaDelCafe(int $idUsuario, int $idDistrito): bool
    {
        $posicion = $this->mundo->posicion($idUsuario);
        if ($posicion === null) {
            return false;
        }
        foreach (self::mapaTiled()['edificios'] as $e) {
            if ($e['tipo'] === 'cafe' && (int) $e['distrito'] === $idDistrito) {
                return hypot($posicion['X'] - $e['puertaX'], $posicion['Y'] - $e['puertaY']) <= self::DISTANCIA_PUERTA + 10;
            }
        }
        return false;
    }

    public function salas(int $idDistrito): array
    {
        return array_map(fn($s) => [
            'id' => (int) $s['IDPartida'], 'nombre' => $s['Nombre'], 'tipo' => $s['Tipo'], 'modo' => $s['Modo'],
            'creador' => $s['Creador'] ?? '', 'jugadores' => (int) $s['Jugadores'], 'max' => (int) $s['MaxJugadores'],
        ], $this->mundo->salasDeDistrito($idDistrito));
    }

    public function crearMesa(int $idUsuario, int $idDistrito, int $jugadores, string $nombreUsuario): array
    {
        if (!$this->cercaDelCafe($idUsuario, $idDistrito)) {
            return ['exito' => false, 'mensaje' => 'Acercate al café para armar una mesa.'];
        }
        if ($jugadores < 2 || $jugadores > 5) {
            return ['exito' => false, 'mensaje' => 'Una mesa es de 2 a 5 jugadores.'];
        }
        $nombreDistrito = '';
        foreach ($this->mundo->distritos() as $d) {
            if ((int) $d['IDDistrito'] === $idDistrito) {
                $nombreDistrito = $d['Nombre'];
            }
        }
        $resultado = (new PartidaLogica())->crearPartida($idUsuario, mb_substr("Mesa de $nombreUsuario en $nombreDistrito", 0, 40), 'publica', '', $jugadores, 'normal');
        if ($resultado['exito']) {
            $this->mundo->asignarDistrito($resultado['idPartida'], $idDistrito);
            (new SocialLogica())->avisarAlGrupo($idUsuario, $resultado['idPartida']);   // el grupo recibe la invitación
        }
        return $resultado;
    }
}
?>
