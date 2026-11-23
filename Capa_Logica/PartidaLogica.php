<?php
require_once(__DIR__ . '/../DTO/PartidasDTO.php');
require_once(__DIR__ . '/../DTO/ConfigPartidaDTO.php');
require_once(__DIR__ . '/../DTO/EstadoPartidaDTO.php');
require_once(__DIR__ . '/IPartidaLogica.php');
require_once(__DIR__ . '/MotorJuego.php');
require_once(__DIR__ . '/SocialLogica.php');
require_once(__DIR__ . '/BotLogica.php');
require_once(__DIR__ . '/RankingLogica.php');
require_once(__DIR__ . '/SeguridadLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * ======================================================================
 *  PARTIDAS EN LÍNEA
 *
 *  El servidor manda: el navegador solo muestra lo que esta clase le
 *  devuelve (vista) y le pide acciones (tirar, colocar, mover...).
 *
 *  Cómo avanza el tiempo sin cron: cada vez que alguien consulta o actúa,
 *  se llama a resolverPendientes(), que mira el reloj de MySQL y hace lo
 *  que corresponda: tirar el dado si se venció el tiempo, jugar por los
 *  bots, cerrar el turno cuando todos confirmaron, marcar abandonos...
 *
 *  Todo pasa dentro de una transacción con la fila de la partida
 *  bloqueada (SELECT ... FOR UPDATE), así dos pedidos simultáneos no se
 *  pisan.
 * ======================================================================
 */
class PartidaLogica implements IPartidaLogica
{
    public const SEGUNDOS_PARA_TIRAR = 15;
    public const SEGUNDOS_ABANDONO = 45;
    public const SEGUNDOS_INACTIVO_EN_SALA = 60;
    public const MODOS_CREABLES = ['normal', 'personalizada'];

    private $persistencia;

    public function __construct()
    {
        $this->persistencia = (new FachadaPersistencia())->retornoIPersistenciaPartidas();
    }

    private static function error(string $mensaje): array
    {
        return ['exito' => false, 'mensaje' => $mensaje];
    }

    private static function ok(array $extra = []): array
    {
        return ['exito' => true] + $extra;
    }

    /**
     * Ejecuta $accion con la partida bloqueada y después resuelve lo pendiente.
     * Si MySQL detecta un bloqueo cruzado (deadlock), reintenta una vez.
     */
    private function enTransaccion(int $idPartida, callable $accion): array
    {
        for ($intento = 1; ; $intento++) {
            $this->persistencia->iniciarTransaccion();
            try {
                if (!$this->persistencia->bloquear($idPartida)) {
                    $this->persistencia->revertir();
                    return self::error('La partida no existe.');
                }
                $resultado = $accion();
                $this->resolverPendientes($idPartida);
                $this->persistencia->confirmar();
                return $resultado;
            } catch (PDOException $e) {
                $this->persistencia->revertir();
                if ($intento === 1 && in_array($e->errorInfo[1] ?? 0, [1213, 1205], true)) {
                    continue;
                }
                throw $e;
            } catch (Throwable $e) {
                $this->persistencia->revertir();
                throw $e;
            }
        }
    }

    /* ======================================================================
     *  CREAR, LISTAR Y UNIRSE
     * ====================================================================== */

    public function crearPartida(int $idUsuario, string $nombre, string $tipo, string $password, int $maxJugadores, string $modo, array $reglas = []): array
    {
        if (!in_array($modo, self::MODOS_CREABLES, true)) {
            return self::error('Ese modo de partida no se puede crear a mano.');
        }
        $actual = $this->persistencia->partidaActivaDeUsuario($idUsuario);
        if ($actual !== null) {
            return self::error('Ya estás en otra partida.') + ['idPartida' => $actual];
        }
        $config = $modo === 'personalizada' ? ConfigPartida::desdeArreglo($reglas) : ConfigPartida::oficial();
        $error = (new MotorJuego($config))->validarParaJugadores($maxJugadores);
        if ($error !== null) {
            return self::error($error);
        }
        $hash = $tipo === 'privada' ? SeguridadLogica::hashPassword($password) : null;

        $this->persistencia->iniciarTransaccion();
        try {
            $id = $this->persistencia->crearPartida($nombre, $tipo, $hash, $modo, $maxJugadores, $idUsuario, $config);
            $this->persistencia->confirmar();
        } catch (Throwable $e) {
            $this->persistencia->revertir();
            throw $e;
        }
        (new AuditoriaLogica())->registrar('partida_creada', 'info', $idUsuario, null, "#$id $modo, $tipo, $maxJugadores jugadores");
        return self::ok(['idPartida' => $id]);
    }

    public function listarSalas(): array
    {
        return $this->persistencia->listarSalas();
    }

    public function partidaActiva(int $idUsuario): ?int
    {
        return $this->persistencia->partidaActivaDeUsuario($idUsuario);
    }

    /** $porInvitacion: un amigo te invitó a su mesa (Fase 4), así que no hace falta la contraseña. */
    public function unirse(int $idUsuario, int $idPartida, string $password, bool $porInvitacion = false): array
    {
        $actual = $this->persistencia->partidaActivaDeUsuario($idUsuario);
        if ($actual === $idPartida) {
            return self::ok(['idPartida' => $idPartida]);
        }
        if ($actual !== null) {
            return self::error('Ya estás en otra partida.') + ['idPartida' => $actual];
        }
        return $this->enTransaccion($idPartida, function () use ($idUsuario, $idPartida, $password, $porInvitacion) {
            $sala = $this->persistencia->datosSala($idPartida);
            if ($sala === null || $sala['Estado'] !== 'esperando') {
                return self::error('Esa partida ya empezó o terminó.');
            }
            if ($sala['Modo'] === 'competitiva') {
                return self::error('A las partidas competitivas se entra por la cola.');
            }
            if ((int) $sala['Jugadores'] >= (int) $sala['MaxJugadores']) {
                return self::error('La partida está llena.');
            }
            if ($sala['Tipo'] === 'privada' && !$porInvitacion && !SeguridadLogica::verificarPassword($password, (string) $sala['HashContra'])) {
                (new AuditoriaLogica())->registrar('unirse_contra_incorrecta', 'aviso', $idUsuario, null, "Partida $idPartida");
                return self::error('La contraseña de la sala no es correcta.');
            }
            $this->persistencia->agregarParticipante($idPartida, $idUsuario, (int) $sala['ProximoAsiento']);
            $this->persistencia->sumarVersion($idPartida);
            return self::ok(['idPartida' => $idPartida]);
        });
    }

    /**
     * Arma una partida competitiva con los jugadores que encontró la cola.
     * Llamar dentro de una transacción (la abre ColaLogica).
     */
    public function crearCompetitiva(array $idsUsuarios): int
    {
        $id = $this->persistencia->crearPartida('Competitiva', 'publica', null, 'competitiva', count($idsUsuarios), $idsUsuarios[0], ConfigPartida::oficial());
        foreach (array_slice($idsUsuarios, 1) as $i => $idUsuario) {
            $this->persistencia->agregarParticipante($id, $idUsuario, $i + 1);
        }
        $this->persistencia->bloquear($id);
        $this->resolverPendientes($id); // ya está llena, así que arranca
        return $id;
    }

    /**
     * Partida de un cruce de torneo: 2 jugadores, reglas oficiales, cerrada (nadie más se sienta)
     * y empieza enseguida. Llamar dentro de una transacción (la abre TorneoLogica).
     */
    public function crearDeTorneo(array $idsUsuarios, string $nombre): int
    {
        $id = $this->persistencia->crearPartida(mb_substr($nombre, 0, 40), 'publica', null, 'normal', count($idsUsuarios), $idsUsuarios[0], ConfigPartida::oficial());
        foreach (array_slice($idsUsuarios, 1) as $i => $idUsuario) {
            $this->persistencia->agregarParticipante($id, $idUsuario, $i + 1);
        }
        $this->persistencia->bloquear($id);
        $this->resolverPendientes($id); // ya está llena, así que arranca
        return $id;
    }

    /**
     * Desafío de una misión (etapa C): el jugador contra el bot de un contacto, con reglas
     * especiales y, si hay escenario, fichas ya colocadas antes de la primera ronda.
     * Llamar dentro de una transacción (la abre MisionLogica).
     * $escenario = ['yo' => [['tipo' => 'peon', 'recinto' => 'oeste'], ...], 'rival' => [...]]
     */
    public function crearDesafio(int $idUsuario, int $idBot, ConfigPartida $config, string $nombre, array $escenario = []): int
    {
        $modo = $config->esOficial() ? 'normal' : 'personalizada';
        $id = $this->persistencia->crearPartida(mb_substr($nombre, 0, 40), 'publica', null, $modo, 2, $idUsuario, $config);
        $this->persistencia->agregarParticipante($id, $idBot, 1);
        $this->persistencia->bloquear($id);
        $this->resolverPendientes($id);   // está llena: arranca y reparte
        if ($escenario !== []) {
            $e = $this->persistencia->cargarEstado($id);
            $usadas = [];
            foreach (['yo' => $idUsuario, 'rival' => $idBot] as $lado => $dueno) {
                $porRecinto = [];
                foreach ($escenario[$lado] ?? [] as $f) {
                    foreach ($e->fichas as $ficha) {
                        if ($ficha['ubicacion'] === 'bolsa' && $ficha['tipo'] === $f['tipo'] && !isset($usadas[$ficha['nro']])) {
                            $usadas[$ficha['nro']] = true;
                            $pos = $porRecinto[$f['recinto']] = ($porRecinto[$f['recinto']] ?? -1) + 1;
                            // Ronda 0: ya estaba en el tablero antes de empezar (se ve y suma como cualquier otra)
                            $this->persistencia->actualizarFicha($id, $ficha['nro'], 'tablero', $dueno, $f['recinto'], $pos, 0, 0);
                            break;
                        }
                    }
                }
            }
            $this->persistencia->sumarVersion($id);
        }
        return $id;
    }

    public function historial(int $idUsuario, int $limite = 10): array
    {
        return $this->persistencia->historialJugador($idUsuario, $limite);
    }

    /* ======================================================================
     *  ACCIONES DEL JUGADOR
     * ====================================================================== */

    public function accion(int $idPartida, int $idUsuario, string $accion, array $datos): array
    {
        return $this->enTransaccion($idPartida, function () use ($idPartida, $idUsuario, $accion, $datos) {
            $e = $this->persistencia->cargarEstado($idPartida);
            if ($e->participante($idUsuario) === null) {
                return self::error('No estás en esta partida.');
            }
            $this->persistencia->latido($idPartida, $idUsuario);

            $nro = (int) ($datos['nro'] ?? 0);
            $recinto = (string) ($datos['recinto'] ?? '');
            $error = match ($accion) {
                'tirar' => $this->tirar($e, $idUsuario),
                'colocar' => $this->colocar($e, $idUsuario, $nro, $recinto, 'colocar'),
                'quitar' => $this->quitar($e, $idUsuario, $nro),
                'mover' => $this->mover($e, $idUsuario, $nro, $recinto),
                'confirmar' => $this->confirmar($e, $idUsuario),
                'salir' => $this->salir($e, $idUsuario),
                'agregar_bot' => $this->agregarBots($e, $idUsuario, 1),
                'completar_bots' => $this->agregarBots($e, $idUsuario, 5),
                default => 'Acción desconocida.',
            };
            return $error === null ? self::ok() : self::error($error);
        });
    }

    private function puedeJugar(EstadoPartida $e, int $idUsuario): ?string
    {
        if ($e->estado() !== 'en_curso') {
            return 'La partida no está en curso.';
        }
        $yo = $e->participante($idUsuario);
        if ($yo === null || $yo['Estado'] !== 'activo') {
            return 'Ya no estás jugando esta partida.';
        }
        return null;
    }

    private function esTirador(EstadoPartida $e, int $idUsuario): bool
    {
        $yo = $e->participante($idUsuario);
        return $yo !== null && $yo['Asiento'] === (int) $e->partida['AsientoDado'];
    }

    private function motor(EstadoPartida $e): MotorJuego
    {
        return new MotorJuego($e->config);
    }

    private function tirar(EstadoPartida $e, int $idUsuario): ?string
    {
        if ($error = $this->puedeJugar($e, $idUsuario)) {
            return $error;
        }
        if ($e->fase() !== 'dado') {
            return 'El dado ya se tiró en este turno.';
        }
        if (!$this->esTirador($e, $idUsuario)) {
            return 'Este turno el dado lo tiene otro jugador.';
        }
        $this->aplicarTirada($e);
        return null;
    }

    private function aplicarTirada(EstadoPartida $e): void
    {
        $cara = $this->motor($e)->tirarDado();
        $this->persistencia->actualizarPartida($e->id(), ['Fase' => 'colocar'], ['InicioFase'], $cara);
    }

    private function colocar(EstadoPartida $e, int $idUsuario, int $nro, string $recinto, string $tipoJugada): ?string
    {
        if ($error = $this->puedeJugar($e, $idUsuario)) {
            return $error;
        }
        if ($e->fase() !== 'colocar') {
            return 'Primero hay que tirar el dado.';
        }
        if ($e->participante($idUsuario)['Confirmado']) {
            return 'Ya confirmaste tu jugada de este turno.';
        }
        if ($e->colocadaEsteTurno($idUsuario) !== null) {
            return 'Ya colocaste una ficha este turno. Usá la X para cambiarla.';
        }
        $mano = $e->manoDe($idUsuario);
        $ficha = null;
        foreach ($mano as $f) {
            if ($f['nro'] === $nro) {
                $ficha = $f;
            }
        }
        if ($ficha === null) {
            return 'Esa ficha no está en tu mano.';
        }
        $tablero = $e->tableroDe($idUsuario);
        $motor = $this->motor($e);
        $error = $motor->validarColocacion($tablero, array_column($mano, 'tipo'), $ficha['tipo'], $recinto, $e->cara(), $this->esTirador($e, $idUsuario));
        if ($error !== null) {
            return $error;
        }
        $posicion = count(MotorJuego::fichasEn($tablero, $recinto)) + 1;
        $this->persistencia->actualizarFicha($e->id(), $nro, 'tablero', $idUsuario, $recinto, $posicion, $e->ronda(), $e->turno());
        $this->persistencia->insertarJugada($e->id(), $nro, $idUsuario, $recinto, null, $tipoJugada, $e->ronda(), $e->turno());
        $this->persistencia->sumarVersion($e->id());
        return null;
    }

    /** La X: devuelve a la mano la ficha colocada en este turno. */
    private function quitar(EstadoPartida $e, int $idUsuario, int $nro): ?string
    {
        if ($error = $this->puedeJugar($e, $idUsuario)) {
            return $error;
        }
        if ($e->participante($idUsuario)['Confirmado']) {
            return 'Ya confirmaste tu jugada de este turno.';
        }
        $colocada = $e->colocadaEsteTurno($idUsuario);
        if ($colocada === null || $colocada['nro'] !== $nro) {
            return 'Solo podés corregir la ficha que colocaste en este turno.';
        }
        $tablero = MotorJuego::quitar($e->tableroDe($idUsuario), $nro);
        $this->persistencia->actualizarFicha($e->id(), $nro, 'mano', $idUsuario);
        $this->persistencia->guardarTablero($e->id(), $tablero);
        $this->persistencia->borrarColocacion($e->id(), $nro, $e->ronda(), $e->turno());
        $this->persistencia->sumarVersion($e->id());
        return null;
    }

    /** Cara "cambiar ficha": el que tiró mueve una ficha propia de un turno anterior. */
    private function mover(EstadoPartida $e, int $idUsuario, int $nro, string $destino): ?string
    {
        if ($error = $this->puedeJugar($e, $idUsuario)) {
            return $error;
        }
        if ($e->fase() !== 'colocar' || $e->cara() !== 'cambiar_ficha' || !$this->esTirador($e, $idUsuario)) {
            return 'Solo el que tiró el dado mueve, y solo con la cara "cambiar ficha".';
        }
        if ($e->participante($idUsuario)['Confirmado']) {
            return 'Ya confirmaste tu jugada de este turno.';
        }
        if ($e->movioEsteTurno($idUsuario)) {
            return 'Ya moviste una ficha este turno.';
        }
        $tablero = $e->tableroDe($idUsuario);
        $movibles = array_column(MotorJuego::fichasMovibles($tablero, $e->ronda(), $e->turno()), 'nro');
        if (!in_array($nro, $movibles, true)) {
            return 'Esa ficha no se puede mover (tiene que ser tuya y de un turno anterior).';
        }
        $motor = $this->motor($e);
        if (!in_array($destino, $motor->destinosMovimiento($tablero, $nro), true)) {
            return 'La ficha no puede ir a ese recinto.';
        }
        $origen = $e->ficha($nro)['recinto'];
        $this->persistencia->guardarTablero($e->id(), $motor->mover($tablero, $nro, $destino));
        $this->persistencia->insertarJugada($e->id(), $nro, $idUsuario, $destino, $origen, 'mover', $e->ronda(), $e->turno());
        $this->persistencia->sumarVersion($e->id());
        return null;
    }

    private function confirmar(EstadoPartida $e, int $idUsuario): ?string
    {
        if ($error = $this->puedeJugar($e, $idUsuario)) {
            return $error;
        }
        if ($e->fase() !== 'colocar') {
            return 'Todavía no se tiró el dado.';
        }
        $motor = $this->motor($e);
        $tablero = $e->tableroDe($idUsuario);
        $esTirador = $this->esTirador($e, $idUsuario);
        if ($motor->debeMover($tablero, $e->cara(), $esTirador, $e->ronda(), $e->turno()) && !$e->movioEsteTurno($idUsuario)) {
            return 'Con esta cara tenés que mover una ficha además de colocar.';
        }
        if ($e->colocadaEsteTurno($idUsuario) === null) {
            $mano = $e->manoDe($idUsuario);
            if ($motor->hayColocacionPosible($tablero, array_column($mano, 'tipo'), $e->cara(), $esTirador)) {
                return 'Tenés que colocar una ficha antes de confirmar.';
            }
            // Ninguna ficha entra en ningún lado (ni en el Muro): se descarta una.
            if ($mano !== []) {
                $this->persistencia->actualizarFicha($e->id(), $mano[0]['nro'], 'retirada', null);
            }
        }
        $this->persistencia->actualizarParticipacion($e->id(), $idUsuario, ['Confirmado' => 1]);
        $this->persistencia->sumarVersion($e->id());
        return null;
    }

    private function salir(EstadoPartida $e, int $idUsuario): ?string
    {
        if ($e->estado() === 'esperando') {
            $this->persistencia->quitarParticipante($e->id(), $idUsuario);
            $quedan = array_values(array_filter($e->participantes, fn($p) => $p['IDUsuario'] !== $idUsuario && !$p['EsBot']));
            if ($quedan === []) {
                $this->persistencia->actualizarPartida($e->id(), ['Estado' => 'cancelada'], ['FechaFin']);
            } elseif ((int) $e->partida['IDCreador'] === $idUsuario) {
                $this->persistencia->actualizarPartida($e->id(), ['IDCreador' => $quedan[0]['IDUsuario']]);
            } else {
                $this->persistencia->sumarVersion($e->id());
            }
            return null;
        }
        if ($e->estado() === 'en_curso') {
            $this->abandonar($e, $idUsuario, 'salió por voluntad propia');
        }
        return null;
    }

    private function agregarBots(EstadoPartida $e, int $idUsuario, int $cantidad): ?string
    {
        if ($e->estado() !== 'esperando') {
            return 'Los bots se agregan antes de empezar.';
        }
        if ($e->modo() === 'competitiva') {
            return 'En competitivo no hay bots.';
        }
        if ((int) $e->partida['IDCreador'] !== $idUsuario) {
            return 'Solo quien creó la sala puede agregar bots.';
        }
        $libres = $this->persistencia->botsLibres();
        $lugares = (int) $e->partida['MaxJugadores'] - count($e->participantes);
        $cantidad = min($cantidad, $lugares, count($libres));
        if ($cantidad < 1) {
            return $lugares < 1 ? 'La sala ya está llena.' : 'No hay bots libres en este momento.';
        }
        $asiento = max(array_column($e->participantes, 'Asiento')) + 1;
        for ($i = 0; $i < $cantidad; $i++) {
            $this->persistencia->agregarParticipante($e->id(), $libres[$i], $asiento + $i);
        }
        $this->persistencia->sumarVersion($e->id());
        return null;
    }

    /* ======================================================================
     *  EL RELOJ: lo que pasa solo
     * ====================================================================== */

    /** Llamar SIEMPRE con la partida ya bloqueada (dentro de una transacción). */
    private function resolverPendientes(int $idPartida): void
    {
        for ($i = 0; $i < 40; $i++) {
            $e = $this->persistencia->cargarEstado($idPartida);
            if ($e === null || !$this->paso($e)) {
                return;
            }
        }
    }

    /** Hace UNA cosa pendiente (si hay) y devuelve true; si no hay nada, false. */
    private function paso(EstadoPartida $e): bool
    {
        if ($e->estado() === 'esperando') {
            return $this->pasoEspera($e);
        }
        if ($e->estado() !== 'en_curso') {
            return false;
        }

        foreach ($e->activos() as $p) {
            if (!$p['EsBot'] && $p['SegundosSinLatido'] > self::SEGUNDOS_ABANDONO) {
                $this->abandonar($e, $p['IDUsuario'], 'se desconectó');
                return true;
            }
        }
        $activos = $e->activos();
        $humanos = array_filter($activos, fn($p) => !$p['EsBot']);
        if (count($activos) <= 1 || $humanos === []) {
            $this->finalizar($e);
            return true;
        }

        if ($e->fase() === 'dado') {
            $tirador = $e->participanteEnAsiento((int) $e->partida['AsientoDado']);
            if ($tirador === null || $tirador['Estado'] !== 'activo') {
                $siguiente = MotorJuego::siguienteAsiento((int) $e->partida['AsientoDado'], array_column($activos, 'Asiento'));
                $this->persistencia->actualizarPartida($e->id(), ['AsientoDado' => $siguiente], ['InicioFase']);
                return true;
            }
            $limite = $tirador['EsBot'] ? BotLogica::demora($tirador['Asiento']) : self::SEGUNDOS_PARA_TIRAR;
            if ($e->segundosFase() >= $limite) {
                $this->aplicarTirada($e);
                return true;
            }
            return false;
        }

        // Fase colocar
        foreach ($activos as $p) {
            if ($p['Confirmado']) {
                continue;
            }
            $limite = $p['EsBot'] ? BotLogica::demora($p['Asiento']) : $e->config->getSegundosTurno();
            if ($e->segundosFase() >= $limite) {
                $this->jugarAutomatico($e, $p);
                return true;
            }
        }
        if (count(array_filter($activos, fn($p) => $p['Confirmado'])) === count($activos)) {
            $this->cerrarTurno($e);
            return true;
        }
        return false;
    }

    private function pasoEspera(EstadoPartida $e): bool
    {
        foreach ($e->participantes as $p) {
            if (!$p['EsBot'] && $p['SegundosSinLatido'] > self::SEGUNDOS_INACTIVO_EN_SALA) {
                $this->salir($e, $p['IDUsuario']);
                return true;
            }
        }
        if (count($e->participantes) >= (int) $e->partida['MaxJugadores']) {
            $this->iniciar($e);
            return true;
        }
        return false;
    }

    /** Juega por un bot o por un humano al que se le venció el tiempo. */
    private function jugarAutomatico(EstadoPartida $e, array $p): void
    {
        $id = $p['IDUsuario'];
        $motor = $this->motor($e);
        $esTirador = $this->esTirador($e, $id);
        $tablero = $e->tableroDe($id);

        if ($motor->debeMover($tablero, $e->cara(), $esTirador, $e->ronda(), $e->turno()) && !$e->movioEsteTurno($id)) {
            $mov = BotLogica::elegirMovimiento($motor, $tablero, $e->ronda(), $e->turno());
            if ($mov !== null) {
                $this->mover($e, $id, $mov['nro'], $mov['destino']);
                $e = $this->persistencia->cargarEstado($e->id());
                $tablero = $e->tableroDe($id);
            }
        }
        if ($e->colocadaEsteTurno($id) === null) {
            $mano = $e->manoDe($id);
            $jugada = $p['EsBot']
                ? BotLogica::elegirColocacion($motor, $p['Nombre'], $tablero, $mano, $e->cara(), $esTirador, $e->ronda(), $e->turno())
                : $motor->mejorColocacion($tablero, $mano, $e->cara(), $esTirador, $e->ronda(), $e->turno());
            if ($jugada !== null) {
                $this->colocar($e, $id, $jugada['nro'], $jugada['recinto'], $p['EsBot'] ? 'colocar' : 'auto');
                $e = $this->persistencia->cargarEstado($e->id());
            }
        }
        $error = $this->confirmar($e, $id);
        if ($error !== null) {
            // No debería pasar: si igual pasa, se confirma para no trabar la partida.
            error_log("Partida {$e->id()}: confirmación automática con aviso para $id: $error");
            $this->persistencia->actualizarParticipacion($e->id(), $id, ['Confirmado' => 1]);
        }
    }

    private function iniciar(EstadoPartida $e): void
    {
        $orden = array_column($e->participantes, 'IDUsuario');
        shuffle($orden); // orden de turnos al azar (RF02)
        $this->persistencia->reasignarAsientos($e->id(), $orden);
        $this->persistencia->insertarBolsa($e->id(), MotorJuego::armarBolsa(count($orden)));
        $this->persistencia->latidoTodos($e->id());
        $this->repartir($e->id(), $orden, $e->config->getFichasPorRonda());
        $this->persistencia->actualizarPartida($e->id(), [
            'Estado' => 'en_curso', 'RondaActual' => 1, 'TurnoActual' => 1, 'Fase' => 'dado', 'AsientoDado' => 0,
        ], ['InicioFase', 'FechaInicio'], '');
    }

    /** Saca de la bolsa $cantidad fichas para cada jugador (la bolsa ya está mezclada). */
    private function repartir(int $idPartida, array $idsEnOrden, int $cantidad): void
    {
        $e = $this->persistencia->cargarEstado($idPartida);
        $bolsa = array_values(array_filter($e->fichas, fn($f) => $f['ubicacion'] === 'bolsa'));
        $i = 0;
        foreach ($idsEnOrden as $idUsuario) {
            for ($k = 0; $k < $cantidad && $i < count($bolsa); $k++, $i++) {
                $this->persistencia->actualizarFicha($idPartida, $bolsa[$i]['nro'], 'mano', $idUsuario);
            }
        }
    }

    private function cerrarTurno(EstadoPartida $e): void
    {
        $activos = $e->activos();
        $asientos = array_column($activos, 'Asiento');

        // Las fichas que quedan en la mano pasan al jugador de la izquierda (asiento siguiente).
        $mapa = [];
        foreach ($activos as $p) {
            $destino = $e->participanteEnAsiento(MotorJuego::siguienteAsiento($p['Asiento'], $asientos));
            foreach ($e->manoDe($p['IDUsuario']) as $f) {
                $mapa[$f['nro']] = $destino['IDUsuario'];
            }
        }
        $this->persistencia->pasarFichasDeMano($e->id(), $mapa);

        $ronda = $e->ronda();
        $turno = $e->turno() + 1;
        if ($e->turno() >= $e->config->getFichasPorRonda()) {
            // Por si quedó algo en alguna mano (no debería): sale de la partida.
            foreach ($e->fichas as $f) {
                if ($f['ubicacion'] === 'mano') {
                    $this->persistencia->actualizarFicha($e->id(), $f['nro'], 'retirada', null);
                }
            }
            if ($ronda >= $e->config->getRondas()) {
                $this->finalizar($this->persistencia->cargarEstado($e->id()));
                return;
            }
            $ronda++;
            $turno = 1;
            $this->repartir($e->id(), array_column($activos, 'IDUsuario'), $e->config->getFichasPorRonda());
        }
        $this->persistencia->desconfirmarTodos($e->id());
        $this->persistencia->actualizarPartida($e->id(), [
            'RondaActual' => $ronda,
            'TurnoActual' => $turno,
            'Fase' => 'dado',
            'AsientoDado' => MotorJuego::siguienteAsiento((int) $e->partida['AsientoDado'], $asientos),
        ], ['InicioFase'], '');
    }

    /** Sección 8.4: pierde los puntos, su mano sale de la partida y el dado pasa si lo tenía. */
    private function abandonar(EstadoPartida $e, int $idUsuario, string $motivo): void
    {
        $yo = $e->participante($idUsuario);
        if ($yo === null || $yo['Estado'] !== 'activo') {
            return;
        }
        $this->persistencia->actualizarParticipacion($e->id(), $idUsuario, ['Estado' => 'abandono', 'Confirmado' => 0]);
        foreach ($e->manoDe($idUsuario) as $f) {
            $this->persistencia->actualizarFicha($e->id(), $f['nro'], 'retirada', null);
        }
        $quedan = array_column(array_filter($e->activos(), fn($p) => $p['IDUsuario'] !== $idUsuario), 'Asiento');
        if ($e->fase() === 'dado' && $yo['Asiento'] === (int) $e->partida['AsientoDado'] && $quedan !== []) {
            $this->persistencia->actualizarPartida($e->id(), [
                'AsientoDado' => MotorJuego::siguienteAsiento($yo['Asiento'], $quedan),
            ], ['InicioFase']);
        } else {
            $this->persistencia->sumarVersion($e->id());
        }
        (new AuditoriaLogica())->registrar('partida_abandono', 'info', $idUsuario, null, "Partida {$e->id()}: $motivo");
    }

    /**
     * Fin de la partida (7.6 y 8.4): puntúa, ordena con desempate, reparte
     * puntos y estadísticas. Reglas de puntos:
     *   - quien abandonó: 0 puntos;
     *   - partida con bots: no da puntos de tienda ni de ranking;
     *   - personalizada: da puntos de tienda pero no de ranking;
     *   - competitiva: además actualiza el MMR.
     */
    private function finalizar(EstadoPartida $e): void
    {
        $motor = $this->motor($e);
        $resultados = [];
        foreach ($e->participantes as $p) {
            $puntos = $motor->puntuar($e->tableroDe($p['IDUsuario']));
            $resultados[$p['IDUsuario']] = ['total' => $puntos['total'], 'politicos' => $puntos['cantidadPoliticos']];
        }
        $activos = array_column($e->activos(), 'IDUsuario');
        $posiciones = MotorJuego::clasificar(array_intersect_key($resultados, array_flip($activos)));

        $hayBots = $e->hayBots();
        $mmrNuevos = [];
        if ($e->modo() === 'competitiva') {
            $entrada = [];
            foreach ($e->participantes as $p) {
                $entrada[$p['IDUsuario']] = ['mmr' => $p['MMR'], 'posicion' => $posiciones[$p['IDUsuario']] ?? null];
            }
            $mmrNuevos = RankingLogica::calcularMMR($entrada);
        }

        foreach ($e->participantes as $p) {
            $id = $p['IDUsuario'];
            $activo = $p['Estado'] === 'activo';
            $total = $resultados[$id]['total'];
            $tienda = ($activo && !$hayBots) ? $total : 0;
            $ranking = ($activo && !$hayBots && $e->modo() !== 'personalizada') ? $total : 0;
            $campos = [
                'PuntajeFinal' => $total,
                'Posicion' => $activo ? $posiciones[$id] : null,
                'PuntosOtorgados' => $tienda,
                'Confirmado' => 0,
            ];
            if (isset($mmrNuevos[$id])) {
                $campos['MMRAntes'] = $p['MMR'];
                $campos['MMRDespues'] = $mmrNuevos[$id];
            }
            $this->persistencia->actualizarParticipacion($e->id(), $id, $campos);
            if (!$p['EsBot']) {
                $this->persistencia->registrarResultado($id, $activo && $posiciones[$id] === 1, $tienda, $ranking, $mmrNuevos[$id] ?? null);
            }
        }
        $this->persistencia->actualizarPartida($e->id(), ['Estado' => 'finalizada'], ['FechaFin']);
        $this->alFinalizar($e);
    }

    /** Mundo abierto: suma influencia al distrito donde se jugó (y el bono por dominarlo). */
    private function alFinalizar(EstadoPartida $e): void
    {
        require_once(__DIR__ . '/MundoLogica.php');
        (new MundoLogica())->registrarPartida($e->id(), $e->modo() === 'competitiva');
    }

    /* ======================================================================
     *  LO QUE VE CADA JUGADOR
     * ====================================================================== */

    /**
     * Devuelve la vista de la partida para $idUsuario, ya recortada:
     * solo su mano, y los tableros ajenos sin lo jugado en el turno activo.
     * Si $versionCliente es la actual, responde corto ('sinCambios').
     */
    public function vista(int $idPartida, int $idUsuario, int $versionCliente = -1): array
    {
        $this->enTransaccion($idPartida, function () use ($idPartida, $idUsuario) {
            $this->persistencia->latido($idPartida, $idUsuario);
            return self::ok();
        });

        $e = $this->persistencia->cargarEstado($idPartida);
        if ($e === null) {
            return self::error('La partida no existe.');
        }
        if ($e->participante($idUsuario) === null) {
            return self::error('No estás en esta partida.');
        }
        $version = (int) $e->partida['Version'];
        $reloj = $this->reloj($e);
        if ($version === $versionCliente) {
            return self::ok(['sinCambios' => true, 'version' => $version] + $reloj);
        }
        return self::ok(['version' => $version] + $reloj + $this->armarVista($e, $idUsuario));
    }

    /**
     * Vista para alguien que MIRA la partida (Fase 4). Es la misma que ve un rival:
     * nunca las manos, y lo jugado en el turno activo recién al cerrarse el turno.
     * No marca latido (mirar no te sienta a la mesa).
     */
    public function vistaEspectador(int $idPartida, int $idUsuario, int $versionCliente = -1): array
    {
        $e = $this->persistencia->cargarEstado($idPartida);
        if ($e === null) {
            return self::error('La partida no existe.');
        }
        if ($e->participante($idUsuario) !== null) {
            return $this->vista($idPartida, $idUsuario, $versionCliente);
        }
        $permiso = (new SocialLogica())->puedeMirar($idUsuario, $idPartida);
        if (!$permiso['exito']) {
            return $permiso;
        }
        $version = (int) $e->partida['Version'];
        $reloj = $this->reloj($e);
        if ($version === $versionCliente) {
            return self::ok(['sinCambios' => true, 'version' => $version, 'espectador' => true] + $reloj);
        }
        return self::ok(['version' => $version] + $reloj + $this->armarVista($e, 0));
    }

    private function reloj(EstadoPartida $e): array
    {
        if ($e->estado() !== 'en_curso') {
            return ['segundosRestantes' => null, 'segundosLimite' => null];
        }
        $limite = $e->fase() === 'dado' ? self::SEGUNDOS_PARA_TIRAR : $e->config->getSegundosTurno();
        return ['segundosRestantes' => max(0, $limite - $e->segundosFase()), 'segundosLimite' => $limite];
    }

    private function armarVista(EstadoPartida $e, int $idUsuario): array
    {
        $motor = $this->motor($e);
        $imagenes = $this->persistencia->imagenesFichas();
        $skins = $this->persistencia->skinsPorJugador(array_column($e->participantes, 'IDUsuario'));
        $imagen = fn(int $id, string $tipo) => $skins[$id][$tipo] ?? ('img/fichas/' . $imagenes[$tipo]);
        $terminada = in_array($e->estado(), ['finalizada', 'cancelada'], true);
        $ocultos = $terminada ? [] : $e->nrosMovidosEsteTurno();
        $nombres = array_column($e->participantes, 'Nombre', 'IDUsuario');

        $jugadores = [];
        foreach ($e->participantes as $p) {
            $esYo = $p['IDUsuario'] === $idUsuario;
            $tablero = $e->tableroDe($p['IDUsuario']);
            if (!$esYo && !$terminada) {
                // Lo jugado en el turno activo no se ve hasta que termine (8.2).
                $tablero = array_values(array_filter($tablero, fn($f) =>
                    !($f['ronda'] === $e->ronda() && $f['turno'] === $e->turno()) && !in_array($f['nro'], $ocultos, true)));
            }
            $puntos = $motor->puntuar($tablero);
            $jugadores[] = [
                'id' => $p['IDUsuario'],
                'nombre' => $p['Nombre'],
                'asiento' => $p['Asiento'],
                'estado' => $p['Estado'],
                'esBot' => (bool) $p['EsBot'],
                'esYo' => $esYo,
                'esCreador' => $p['IDUsuario'] === (int) $e->partida['IDCreador'],
                'tieneDado' => $p['Asiento'] === (int) $e->partida['AsientoDado'] && $e->estado() === 'en_curso',
                'confirmado' => (bool) $p['Confirmado'],
                'fichasEnMano' => count($e->manoDe($p['IDUsuario'])),
                'mmr' => $p['MMR'],
                'tablero' => array_map(fn($f) => [
                    'nro' => $f['nro'], 'tipo' => $f['tipo'], 'recinto' => $f['recinto'], 'posicion' => $f['posicion'],
                    'actual' => $f['ronda'] === $e->ronda() && $f['turno'] === $e->turno() && !$terminada,
                    'puntos' => $puntos['detalle'][$f['nro']] ?? 0,
                    'img' => $imagen($p['IDUsuario'], $f['tipo']),
                ], $tablero),
                'puntos' => ['total' => $puntos['total'], 'recintos' => $puntos['recintos'], 'politicos' => $puntos['politicos']],
                'resultado' => $terminada ? [
                    'posicion' => $p['Posicion'] === null ? null : (int) $p['Posicion'],
                    'puntaje' => $p['PuntajeFinal'] === null ? null : (int) $p['PuntajeFinal'],
                    'otorgados' => (int) $p['PuntosOtorgados'],
                    'mmrAntes' => $p['MMRAntes'] === null ? null : (int) $p['MMRAntes'],
                    'mmrDespues' => $p['MMRDespues'] === null ? null : (int) $p['MMRDespues'],
                ] : null,
            ];
        }

        // Mi parte: mano, opciones y obligaciones del turno.
        $yo = $e->participante($idUsuario);   // null = espectador
        $mio = ['activo' => $yo !== null && $yo['Estado'] === 'activo', 'mano' => [], 'opciones' => new stdClass(), 'debeMover' => false,
            'movio' => false, 'movibles' => [], 'colocada' => null, 'esTirador' => $yo !== null && $this->esTirador($e, $idUsuario),
            'confirmado' => $yo !== null && (bool) $yo['Confirmado'], 'puedeConfirmar' => false, 'sinLugar' => false];
        if ($yo !== null && $e->estado() === 'en_curso' && $yo['Estado'] === 'activo') {
            $mano = $e->manoDe($idUsuario);
            $tablero = $e->tableroDe($idUsuario);
            $mio['mano'] = array_map(fn($f) => $f + ['img' => $imagen($idUsuario, $f['tipo'])], $mano);
            $colocada = $e->colocadaEsteTurno($idUsuario);
            $mio['colocada'] = $colocada['nro'] ?? null;
            if ($e->fase() === 'colocar' && !$yo['Confirmado']) {
                if ($colocada === null && $mano !== []) {
                    $mio['opciones'] = $motor->opcionesColocacion($tablero, array_column($mano, 'tipo'), $e->cara(), $mio['esTirador']);
                }
                $mio['movio'] = $e->movioEsteTurno($idUsuario);
                $mio['debeMover'] = $motor->debeMover($tablero, $e->cara(), $mio['esTirador'], $e->ronda(), $e->turno()) && !$mio['movio'];
                if ($mio['debeMover']) {
                    foreach (MotorJuego::fichasMovibles($tablero, $e->ronda(), $e->turno()) as $f) {
                        $destinos = $motor->destinosMovimiento($tablero, $f['nro']);
                        if ($destinos !== []) {
                            $mio['movibles'][] = ['nro' => $f['nro'], 'destinos' => $destinos];
                        }
                    }
                }
                $mio['sinLugar'] = $colocada === null && !$motor->hayColocacionPosible($tablero, array_column($mano, 'tipo'), $e->cara(), $mio['esTirador']);
                $mio['puedeConfirmar'] = !$mio['debeMover'] && ($colocada !== null || $mio['sinLugar']);
            }
        }

        // Historial: solo turnos ya cerrados (8.3).
        $historial = [];
        foreach ($e->jugadas as $j) {
            $cerrado = $terminada || $j['ronda'] < $e->ronda() || ($j['ronda'] === $e->ronda() && $j['turno'] < $e->turno());
            if ($cerrado) {
                $historial[] = [
                    'ronda' => $j['ronda'], 'turno' => $j['turno'], 'jugador' => $nombres[$j['idUsuario']] ?? '?',
                    'tipo' => $j['tipo'], 'ficha' => $e->ficha($j['nro'])['tipo'] ?? '', 'recinto' => $j['recinto'], 'origen' => $j['origen'],
                ];
            }
        }

        $recintos = array_keys(MotorJuego::RECINTOS);
        return [
            'id' => $e->id(),
            'nombre' => $e->partida['Nombre'],
            'estado' => $e->estado(),
            'modo' => $e->modo(),
            'tipo' => $e->partida['Tipo'],
            'maxJugadores' => (int) $e->partida['MaxJugadores'],
            'ronda' => $e->ronda(),
            'turno' => $e->turno(),
            'fase' => $e->fase(),
            'cara' => $e->cara(),
            'config' => $e->config->aArreglo(),
            'capacidades' => array_combine($recintos, array_map(fn($r) => $motor->capacidad($r), $recintos)),
            'jugadores' => $jugadores,
            'yo' => $mio,
            'espectador' => $yo === null,
            'historial' => array_reverse($historial),
            'hayBots' => $e->hayBots(),
            'puedeAgregarBot' => $e->estado() === 'esperando' && $e->modo() !== 'competitiva'
                && (int) $e->partida['IDCreador'] === $idUsuario && count($e->participantes) < (int) $e->partida['MaxJugadores'],
        ];
    }
}
?>
