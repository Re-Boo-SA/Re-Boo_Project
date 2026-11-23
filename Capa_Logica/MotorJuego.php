<?php
require_once(__DIR__ . '/../DTO/ConfigPartidaDTO.php');

/*
 * ======================================================================
 *  MOTOR DE REGLAS DE DRAFT DER MAUER
 *
 *  Aplica el manual "Draftosaurus por DRAFT DER MAUER" sin tocar la base
 *  de datos: recibe arreglos y devuelve arreglos. Por eso se puede probar
 *  solo (tests/motor_test.php) y lo usan igual la partida online, los
 *  bots, la calculadora y el modo competitivo.
 *
 *  Cómo se representa un tablero: una lista de fichas, cada una así
 *      ['nro' => 12, 'tipo' => 'cabo', 'recinto' => 'fabrica',
 *       'posicion' => 1, 'ronda' => 1, 'turno' => 3]
 *  'posicion' empieza en 1 y va de izquierda a derecha dentro del recinto.
 * ======================================================================
 */
class MotorJuego
{
    /* ---------- Catálogo (coincide con las tablas FICHAS y RECINTOS) ---------- */

    public const CATEGORIA_DE = [
        'general' => 'militares', 'oficial_militar' => 'militares', 'tropa' => 'militares',
        'cabo' => 'obreros', 'oficial_obrera' => 'obreros', 'peon' => 'obreros',
        'politico' => 'politicos',
        'markus' => 'espias', 'ursula' => 'espias',
    ];

    public const TIPOS_DE_CATEGORIA = [
        'militares' => ['general', 'oficial_militar', 'tropa'],
        'obreros' => ['cabo', 'oficial_obrera', 'peon'],
        'politicos' => ['politico'],
        'espias' => ['markus', 'ursula'],
    ];

    /** Cantidad de cada ficha en el juego completo: 60 en total (sección 2.4). */
    public const CANTIDAD_TOTAL = [
        'general' => 6, 'oficial_militar' => 6, 'tropa' => 6,
        'cabo' => 6, 'oficial_obrera' => 6, 'peon' => 6,
        'politico' => 14, 'markus' => 5, 'ursula' => 5,
    ];

    /** Fichas que se retiran según la cantidad de jugadores, por categoría. */
    public const RETIRADAS = [
        2 => ['espias' => 2, 'politicos' => 2, 'militares' => 3, 'obreros' => 3],
        3 => ['espias' => 4, 'politicos' => 4, 'militares' => 3, 'obreros' => 3],
        4 => ['espias' => 2, 'politicos' => 2, 'militares' => 3, 'obreros' => 3],
        5 => ['espias' => 0, 'politicos' => 0, 'militares' => 0, 'obreros' => 0],
    ];

    /** Recintos: capacidad, lado del tablero y categoría exclusiva (null = libre). */
    public const RECINTOS = [
        'oeste' => ['capacidad' => 6, 'lado' => 'izquierdo', 'exclusiva' => null],
        'fabrica' => ['capacidad' => 3, 'lado' => 'izquierdo', 'exclusiva' => 'obreros'],
        'espias' => ['capacidad' => 2, 'lado' => 'izquierdo', 'exclusiva' => 'espias'],
        'politicos' => ['capacidad' => 1, 'lado' => 'derecho', 'exclusiva' => 'politicos'],
        'este' => ['capacidad' => 6, 'lado' => 'derecho', 'exclusiva' => null],
        'militares' => ['capacidad' => 3, 'lado' => 'derecho', 'exclusiva' => 'militares'],
        'muro' => ['capacidad' => 12, 'lado' => 'centro', 'exclusiva' => null],
    ];

    public const CARAS = ConfigPartida::TODAS_LAS_CARAS;

    private ConfigPartida $config;

    public function __construct(?ConfigPartida $config = null)
    {
        $this->config = $config ?? ConfigPartida::oficial();
    }

    public function getConfig(): ConfigPartida
    {
        return $this->config;
    }

    /* ======================================================================
     *  BOLSA Y REPARTO
     * ====================================================================== */

    /** Cuántas fichas de cada tipo hay en la bolsa según los jugadores. */
    public static function composicionBolsa(int $jugadores): array
    {
        if (!isset(self::RETIRADAS[$jugadores])) {
            throw new InvalidArgumentException('La partida admite de 2 a 5 jugadores.');
        }
        $bolsa = self::CANTIDAD_TOTAL;
        foreach (self::RETIRADAS[$jugadores] as $categoria => $cantidad) {
            $tipos = self::TIPOS_DE_CATEGORIA[$categoria];
            // Se retira en partes iguales de cada tipo de la categoría
            // (3 militares = 1 de cada tipo; 4 espías = 2 Markus y 2 Ursula).
            for ($i = 0; $i < $cantidad; $i++) {
                $bolsa[$tipos[$i % count($tipos)]]--;
            }
        }
        return $bolsa;
    }

    /** La bolsa ya mezclada: una lista de códigos de ficha en orden aleatorio. */
    public static function armarBolsa(int $jugadores): array
    {
        $lista = [];
        foreach (self::composicionBolsa($jugadores) as $tipo => $cantidad) {
            for ($i = 0; $i < $cantidad; $i++) {
                $lista[] = $tipo;
            }
        }
        // Fisher-Yates con random_int (aleatorio criptográfico, nadie lo predice).
        for ($i = count($lista) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$lista[$i], $lista[$j]] = [$lista[$j], $lista[$i]];
        }
        return $lista;
    }

    /** ¿Alcanzan las fichas de la bolsa para estas reglas y estos jugadores? */
    public function validarParaJugadores(int $jugadores): ?string
    {
        $error = $this->config->validar();
        if ($error !== null) {
            return $error;
        }
        $necesarias = $jugadores * $this->config->getRondas() * $this->config->getFichasPorRonda();
        $disponibles = array_sum(self::composicionBolsa($jugadores));
        if ($necesarias > $disponibles) {
            return "Con $jugadores jugadores hacen falta $necesarias fichas y la bolsa tiene $disponibles. "
                . 'Bajá las rondas o las fichas por ronda.';
        }
        return null;
    }

    /** Asiento que tira el dado en un turno: arranca en uno al azar y pasa a la izquierda. */
    public static function siguienteAsiento(int $asiento, array $asientosActivos): int
    {
        sort($asientosActivos);
        foreach ($asientosActivos as $candidato) {
            if ($candidato > $asiento) {
                return $candidato;
            }
        }
        return $asientosActivos[0];
    }

    /* ======================================================================
     *  DADO
     * ====================================================================== */

    public function tirarDado(): string
    {
        $caras = $this->config->getCaras();
        return $caras[random_int(0, count($caras) - 1)];
    }

    /**
     * Recintos donde el dado deja colocar. El Muro nunca se bloquea.
     * Quien tiró el dado no sufre la restricción si la regla TiradorExento
     * está activa (así funciona Draftosaurus y así lo hacía la calculadora).
     */
    public function recintosPermitidosPorDado(?string $cara, bool $esTirador): array
    {
        $todos = array_keys(self::RECINTOS);
        if ($cara === null || ($esTirador && $this->config->getTiradorExento())) {
            return $todos;
        }
        $filtro = match ($cara) {
            'lado_izquierdo' => fn($r) => self::RECINTOS[$r]['lado'] !== 'izquierdo',
            'lado_derecho' => fn($r) => self::RECINTOS[$r]['lado'] !== 'derecho',
            'solo_militares' => fn($r) => $r === 'militares' || $r === 'muro',
            'bloquea_fabrica' => fn($r) => $r !== 'fabrica',
            'solo_espias' => fn($r) => $r === 'espias' || $r === 'muro',
            default => fn($r) => true, // cambiar_ficha no restringe la colocación
        };
        return array_values(array_filter($todos, $filtro));
    }

    /* ======================================================================
     *  COLOCACIÓN
     * ====================================================================== */

    public function capacidad(string $recinto): int
    {
        return $recinto === 'muro' ? $this->config->getCapacidadMuro() : self::RECINTOS[$recinto]['capacidad'];
    }

    public static function fichasEn(array $tablero, string $recinto): array
    {
        $fichas = array_values(array_filter($tablero, fn($f) => $f['recinto'] === $recinto));
        usort($fichas, fn($a, $b) => $a['posicion'] <=> $b['posicion']);
        return $fichas;
    }

    public static function hayPoliticoEnSuRecinto(array $tablero): bool
    {
        foreach ($tablero as $ficha) {
            if ($ficha['recinto'] === 'politicos' && $ficha['tipo'] === 'politico') {
                return true;
            }
        }
        return false;
    }

    /**
     * Valida si una ficha de tipo $tipo puede ir al $recinto sin mirar el dado.
     * Devuelve null si se puede, o el motivo (para mostrarlo al jugador).
     * $excluirNro: ficha que se está moviendo (no cuenta como ocupando lugar).
     */
    public function motivoRecinto(array $tablero, string $tipo, string $recinto, ?int $excluirNro = null): ?string
    {
        if (!isset(self::RECINTOS[$recinto])) {
            return 'Ese recinto no existe.';
        }
        if (!isset(self::CATEGORIA_DE[$tipo])) {
            return 'Esa ficha no existe.';
        }
        $tableroSinLaFicha = $excluirNro === null
            ? $tablero
            : array_values(array_filter($tablero, fn($f) => $f['nro'] !== $excluirNro));

        if (count(self::fichasEn($tableroSinLaFicha, $recinto)) >= $this->capacidad($recinto)) {
            return 'El recinto ya está completo.';
        }

        $categoria = self::CATEGORIA_DE[$tipo];
        $exclusiva = self::RECINTOS[$recinto]['exclusiva'];

        if ($categoria === 'politicos') {
            // El político puede ir a cualquier recinto, pero fuera del suyo
            // solo cuando ya hay uno en Políticos (sección 5.6).
            if ($recinto !== 'politicos' && $this->config->getReglaPolitico()
                && !self::hayPoliticoEnSuRecinto($tableroSinLaFicha)) {
                return 'Primero tiene que haber un político en el recinto Políticos.';
            }
            return null;
        }

        if ($exclusiva !== null && $exclusiva !== $categoria) {
            return 'En este recinto solo van fichas de ' . $exclusiva . '.';
        }
        return null;
    }

    /** Igual que motivoRecinto pero sumando la restricción del dado. */
    public function motivoColocar(array $tablero, string $tipo, string $recinto, ?string $cara, bool $esTirador): ?string
    {
        if (!in_array($recinto, $this->recintosPermitidosPorDado($cara, $esTirador), true)) {
            return 'El dado no deja colocar en este recinto este turno.';
        }
        return $this->motivoRecinto($tablero, $tipo, $recinto);
    }

    /**
     * Para cada tipo de la mano, la lista de recintos donde puede ir ahora.
     *
     * Caso especial (hueco del manual, decidido con el equipo): si NINGUNA
     * ficha de la mano entra en ningún recinto (por ejemplo, solo te quedan
     * políticos, el dado dice "Solo Militares" y todavía no abriste
     * Políticos), cualquier ficha puede ir al Muro, que existe justamente
     * como salida. Si el Muro también está lleno, la lista queda vacía y la
     * ficha se descarta.
     */
    public function opcionesColocacion(array $tablero, array $manoTipos, ?string $cara, bool $esTirador): array
    {
        $opciones = [];
        $hayAlguna = false;
        foreach (array_unique($manoTipos) as $tipo) {
            $opciones[$tipo] = [];
            foreach (array_keys(self::RECINTOS) as $recinto) {
                if ($this->motivoColocar($tablero, $tipo, $recinto, $cara, $esTirador) === null) {
                    $opciones[$tipo][] = $recinto;
                    $hayAlguna = true;
                }
            }
        }
        if (!$hayAlguna && $this->muroDisponible($tablero)) {
            foreach ($opciones as $tipo => $recintos) {
                $opciones[$tipo] = ['muro'];
            }
        }
        return $opciones;
    }

    private function muroDisponible(array $tablero): bool
    {
        return count(self::fichasEn($tablero, 'muro')) < $this->capacidad('muro');
    }

    /**
     * Validación completa de una colocación, mirando la mano entera (por el
     * caso especial del Muro). Devuelve null si vale o el motivo si no.
     */
    public function validarColocacion(array $tablero, array $manoTipos, string $tipo, string $recinto, ?string $cara, bool $esTirador): ?string
    {
        if (!in_array($tipo, $manoTipos, true)) {
            return 'Esa ficha no está en tu mano.';
        }
        $opciones = $this->opcionesColocacion($tablero, $manoTipos, $cara, $esTirador);
        if (in_array($recinto, $opciones[$tipo], true)) {
            return null;
        }
        return $this->motivoColocar($tablero, $tipo, $recinto, $cara, $esTirador)
            ?? 'Esa ficha no puede ir ahí este turno.';
    }

    /** ¿Hay al menos una ficha de la mano que entre en algún lado? */
    public function hayColocacionPosible(array $tablero, array $manoTipos, ?string $cara, bool $esTirador): bool
    {
        foreach ($this->opcionesColocacion($tablero, $manoTipos, $cara, $esTirador) as $recintos) {
            if ($recintos !== []) {
                return true;
            }
        }
        return false;
    }

    /** Devuelve el tablero con la ficha agregada al final del recinto. */
    public function colocar(array $tablero, int $nro, string $tipo, string $recinto, int $ronda, int $turno): array
    {
        $tablero[] = [
            'nro' => $nro,
            'tipo' => $tipo,
            'recinto' => $recinto,
            'posicion' => count(self::fichasEn($tablero, $recinto)) + 1,
            'ronda' => $ronda,
            'turno' => $turno,
        ];
        return $tablero;
    }

    /** Saca una ficha y corre a la izquierda las que estaban después (para la X y los movimientos). */
    public static function quitar(array $tablero, int $nro): array
    {
        $recinto = null;
        $posicion = 0;
        foreach ($tablero as $i => $ficha) {
            if ($ficha['nro'] === $nro) {
                $recinto = $ficha['recinto'];
                $posicion = $ficha['posicion'];
                unset($tablero[$i]);
            }
        }
        foreach ($tablero as $i => $ficha) {
            if ($ficha['recinto'] === $recinto && $ficha['posicion'] > $posicion) {
                $tablero[$i]['posicion']--;
            }
        }
        return array_values($tablero);
    }

    /* ======================================================================
     *  CARA "CAMBIAR FICHA" (mover una ficha propia ya colocada)
     * ====================================================================== */

    /** Fichas que se pueden mover: las de turnos anteriores, no la del turno actual. */
    public static function fichasMovibles(array $tablero, int $ronda, int $turno): array
    {
        return array_values(array_filter(
            $tablero,
            fn($f) => !($f['ronda'] === $ronda && $f['turno'] === $turno)
        ));
    }

    /** Recintos a los que se puede llevar la ficha $nro (el dado no aplica al que tiró). */
    public function destinosMovimiento(array $tablero, int $nro): array
    {
        $ficha = null;
        foreach ($tablero as $f) {
            if ($f['nro'] === $nro) {
                $ficha = $f;
            }
        }
        if ($ficha === null) {
            return [];
        }
        // No se puede sacar el único político de Políticos si hay otros afuera:
        // dejaría políticos colocados sin la condición previa cumplida.
        if ($ficha['tipo'] === 'politico' && $ficha['recinto'] === 'politicos' && $this->config->getReglaPolitico()) {
            foreach ($tablero as $f) {
                if ($f['tipo'] === 'politico' && $f['recinto'] !== 'politicos') {
                    return [];
                }
            }
        }
        $destinos = [];
        foreach (array_keys(self::RECINTOS) as $recinto) {
            if ($recinto !== $ficha['recinto'] && $this->motivoRecinto($tablero, $ficha['tipo'], $recinto, $nro) === null) {
                $destinos[] = $recinto;
            }
        }
        return $destinos;
    }

    /**
     * ¿Este jugador está obligado a mover una ficha este turno?
     * Solo quien tiró el dado, solo con la cara cambiar_ficha, y solo si
     * tiene alguna ficha de turnos anteriores que se pueda llevar a otro lado.
     */
    public function debeMover(array $tablero, ?string $cara, bool $esTirador, int $ronda, int $turno): bool
    {
        if ($cara !== 'cambiar_ficha' || !$esTirador) {
            return false;
        }
        foreach (self::fichasMovibles($tablero, $ronda, $turno) as $ficha) {
            if ($this->destinosMovimiento($tablero, $ficha['nro']) !== []) {
                return true;
            }
        }
        return false;
    }

    /** Lleva la ficha $nro al final del recinto $destino. */
    public function mover(array $tablero, int $nro, string $destino): array
    {
        $ficha = null;
        foreach ($tablero as $f) {
            if ($f['nro'] === $nro) {
                $ficha = $f;
            }
        }
        if ($ficha === null) {
            throw new InvalidArgumentException('La ficha a mover no está en el tablero.');
        }
        $tablero = self::quitar($tablero, $nro);
        $ficha['recinto'] = $destino;
        $ficha['posicion'] = count(self::fichasEn($tablero, $destino)) + 1;
        $tablero[] = $ficha;
        return $tablero;
    }

    /* ======================================================================
     *  PUNTUACIÓN (secciones 5 y 6)
     * ====================================================================== */

    /**
     * Calcula la puntuación desde el estado real del tablero.
     * Devuelve:
     *   'recintos'  => puntos básicos de cada recinto
     *   'politicos' => 1 punto por cada político, esté donde esté
     *   'total'     => suma de todo
     *   'cantidadPoliticos' => para el desempate
     *   'detalle'   => puntos de cada ficha (nro => puntos), para mostrarlos
     * Los puntos del político se separan de los del recinto para no contarlos dos veces.
     */
    public function puntuar(array $tablero): array
    {
        $recintos = array_fill_keys(array_keys(self::RECINTOS), 0);
        $detalle = [];
        $cantidadPoliticos = 0;

        foreach (['fabrica', 'militares'] as $recinto) {
            $valores = $this->valoresPorTipo($tablero, $recinto);
            foreach (self::fichasEn($tablero, $recinto) as $ficha) {
                $puntos = $valores[$ficha['tipo']] ?? 0;
                $recintos[$recinto] += $puntos;
                $detalle[$ficha['nro']] = $puntos;
            }
        }

        $espias = self::fichasEn($tablero, 'espias');
        $tiposEspias = array_column($espias, 'tipo');
        $parejaCompleta = in_array('markus', $tiposEspias, true) && in_array('ursula', $tiposEspias, true);
        if ($parejaCompleta) {
            $recintos['espias'] = $this->config->getPuntosEspias();
        }
        foreach ($espias as $ficha) {
            $detalle[$ficha['nro']] = 0;
        }
        if ($parejaCompleta) {
            // Para el detalle, la combinación se reparte entre las dos fichas.
            $mitad = intdiv($this->config->getPuntosEspias(), 2);
            $marcadas = ['markus' => false, 'ursula' => false];
            foreach ($espias as $ficha) {
                if (isset($marcadas[$ficha['tipo']]) && !$marcadas[$ficha['tipo']]) {
                    $detalle[$ficha['nro']] = $ficha['tipo'] === 'markus'
                        ? $mitad : $this->config->getPuntosEspias() - $mitad;
                    $marcadas[$ficha['tipo']] = true;
                }
            }
        }

        foreach (['oeste', 'este', 'muro'] as $recinto) {
            foreach (self::fichasEn($tablero, $recinto) as $ficha) {
                $recintos[$recinto] += 1;
                $detalle[$ficha['nro']] = 1;
            }
        }

        foreach ($tablero as $ficha) {
            $detalle[$ficha['nro']] = $detalle[$ficha['nro']] ?? 0;
            if ($ficha['tipo'] === 'politico') {
                $cantidadPoliticos++;
                $detalle[$ficha['nro']] += 1;
            }
        }

        return [
            'recintos' => $recintos,
            'politicos' => $cantidadPoliticos,
            'total' => array_sum($recintos) + $cantidadPoliticos,
            'cantidadPoliticos' => $cantidadPoliticos,
            'detalle' => $detalle,
        ];
    }

    /**
     * Fábrica y Militares: el primer tipo distinto que aparece (de izquierda
     * a derecha) vale 1, el segundo 3 y el tercero 7. Las repeticiones valen
     * lo mismo que su tipo. Los políticos ocupan lugar pero no son un tipo.
     */
    private function valoresPorTipo(array $tablero, string $recinto): array
    {
        $tiposValidos = self::TIPOS_DE_CATEGORIA[self::RECINTOS[$recinto]['exclusiva']];
        $escala = $this->config->getValoresTipos();
        $valores = [];
        foreach (self::fichasEn($tablero, $recinto) as $ficha) {
            if (in_array($ficha['tipo'], $tiposValidos, true) && !isset($valores[$ficha['tipo']])) {
                $valores[$ficha['tipo']] = $escala[count($valores)] ?? 0;
            }
        }
        return $valores;
    }

    /**
     * Posiciones finales. Recibe [id => ['total' => int, 'politicos' => int]].
     * Más puntos gana; si empatan, gana quien tenga menos políticos; si sigue
     * el empate, comparten el puesto (1, 1, 3...).
     */
    public static function clasificar(array $resultados): array
    {
        $ids = array_keys($resultados);
        usort($ids, function ($a, $b) use ($resultados) {
            return [$resultados[$b]['total'], $resultados[$a]['politicos']]
                <=> [$resultados[$a]['total'], $resultados[$b]['politicos']];
        });
        $posiciones = [];
        foreach ($ids as $indice => $id) {
            if ($indice > 0) {
                $anterior = $ids[$indice - 1];
                if ($resultados[$anterior]['total'] === $resultados[$id]['total']
                    && $resultados[$anterior]['politicos'] === $resultados[$id]['politicos']) {
                    $posiciones[$id] = $posiciones[$anterior];
                    continue;
                }
            }
            $posiciones[$id] = $indice + 1;
        }
        return $posiciones;
    }

    /* ======================================================================
     *  JUGADA AUTOMÁTICA (tiempo vencido y base de los bots)
     * ====================================================================== */

    /**
     * Elige la colocación que más puntos suma ahora. $mano es una lista de
     * ['nro' => int, 'tipo' => string]. $ruido (0 a 1) agrega azar para que
     * los bots no jueguen siempre igual. Devuelve ['nro', 'tipo', 'recinto']
     * o null si ninguna ficha entra en ningún lado.
     */
    public function mejorColocacion(array $tablero, array $mano, ?string $cara, bool $esTirador, int $ronda, int $turno, float $ruido = 0.0, array $preferencias = []): ?array
    {
        $base = $this->puntuar($tablero)['total'];
        $mejor = null;
        $mejorValor = -INF;
        $opciones = $this->opcionesColocacion($tablero, array_column($mano, 'tipo'), $cara, $esTirador);
        foreach ($mano as $fichaMano) {
            foreach ($opciones[$fichaMano['tipo']] as $recinto) {
                $nuevo = $this->colocar($tablero, $fichaMano['nro'], $fichaMano['tipo'], $recinto, $ronda, $turno);
                $valor = $this->puntuar($nuevo)['total'] - $base
                    + $this->potencial($nuevo, $recinto)
                    + ($preferencias[$recinto] ?? 0)
                    + $ruido * (random_int(0, 1000) / 1000) * 3;
                if ($valor > $mejorValor) {
                    $mejorValor = $valor;
                    $mejor = ['nro' => $fichaMano['nro'], 'tipo' => $fichaMano['tipo'], 'recinto' => $recinto];
                }
            }
        }
        return $mejor;
    }

    /** Pequeño premio por jugadas que preparan puntos futuros (no es puntaje real). */
    private function potencial(array $tablero, string $recinto): float
    {
        $fichas = self::fichasEn($tablero, $recinto);
        return match ($recinto) {
            // Abrir Políticos habilita los políticos en todos lados.
            'politicos' => 1.5,
            // Tener un espía solo es medio camino a la pareja.
            'espias' => count(array_unique(array_column($fichas, 'tipo'))) === 1 ? 1.5 : 0,
            // Fábrica y Militares premian variedad: un tipo nuevo promete el 3 o el 7.
            'fabrica', 'militares' => count($fichas) < 3 ? 0.5 * count(array_unique(array_column($fichas, 'tipo'))) : 0,
            // El Muro es la salida de emergencia: se usa poco.
            'muro' => -0.6,
            default => 0,
        };
    }

    /** Movimiento automático para la cara cambiar_ficha: el que más mejora el puntaje. */
    public function mejorMovimiento(array $tablero, int $ronda, int $turno): ?array
    {
        $base = $this->puntuar($tablero)['total'];
        $mejor = null;
        $mejorValor = -INF;
        foreach (self::fichasMovibles($tablero, $ronda, $turno) as $ficha) {
            foreach ($this->destinosMovimiento($tablero, $ficha['nro']) as $destino) {
                $valor = $this->puntuar($this->mover($tablero, $ficha['nro'], $destino))['total'] - $base;
                if ($valor > $mejorValor) {
                    $mejorValor = $valor;
                    $mejor = ['nro' => $ficha['nro'], 'destino' => $destino];
                }
            }
        }
        return $mejor;
    }
}
?>
