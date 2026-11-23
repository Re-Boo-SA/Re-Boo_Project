<?php
require_once(__DIR__ . '/ISocialLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/RankingLogica.php');
require_once(__DIR__ . '/MisionLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * ======================================================================
 *  SOCIAL (Fase 4): perfil público, amigos, bloqueos, grupos e invitaciones.
 *
 *  Reglas:
 *   - Una sola amistad por par. Si B ya te pidió y vos le pedís, se acepta sola.
 *   - Bloquear borra la amistad y las invitaciones entre los dos; desde ahí no se
 *     pueden pedir amistad, invitar ni verse en Berlín.
 *   - Dónde está alguien (presencia) solo lo ven sus amigos.
 *   - Grupos de hasta 4; un jugador está en un solo grupo. El tamaño se controla con
 *     la fila del grupo bloqueada (FOR UPDATE).
 *   - Una partida privada solo la puede mirar un amigo de alguno de sus jugadores.
 * ======================================================================
 */
class SocialLogica implements ISocialLogica
{
    public const MAX_GRUPO = 4;

    private $social;
    private $mundo;

    public function __construct()
    {
        $f = new FachadaPersistencia();
        $this->social = $f->retornoIPersistenciaSocial();
        $this->mundo = $f->retornoIPersistenciaMundo();
    }

    private static function ok(array $extra = []): array { return ['exito' => true] + $extra; }
    private static function error(string $m): array { return ['exito' => false, 'mensaje' => $m]; }

    private function esJugadorHumano(?array $j): bool
    {
        return $j !== null && $j['Rol'] === 'jugador' && !(bool) $j['EsBot'];
    }

    private function sonAmigos(int $a, int $b): bool
    {
        $am = $this->social->amistad($a, $b);
        return $am !== null && $am['Estado'] === 'aceptada';
    }

    private function relacion(int $yo, int $otro): string
    {
        if ($yo === $otro) {
            return 'yo';
        }
        if ($this->social->bloqueado($yo, $otro)) {
            return 'bloqueado';
        }
        $am = $this->social->amistad($yo, $otro);
        if ($am === null) {
            return 'ninguna';
        }
        if ($am['Estado'] === 'aceptada') {
            return 'amigos';
        }
        return (int) $am['IDSolicitante'] === $yo ? 'enviada' : 'recibida';
    }

    /** Presencias legibles: dónde está cada uno (solo se usa para amigos y miembros del grupo). */
    private function conPresencia(array $filas): array
    {
        $pres = $this->social->presencias(array_map(fn($f) => (int) $f['IDUsuario'], $filas));
        $distritos = $this->mundo->distritos();
        $nombres = array_column($distritos, 'Nombre', 'IDDistrito');
        return array_map(function ($f) use ($pres, $distritos, $nombres) {
            $p = $pres[(int) $f['IDUsuario']] ?? null;
            $estado = ['estado' => 'desconectado'];
            if ($p !== null && $p['donde'] === 'berlin') {
                $d = MundoLogica::distritoEn($p['x'], $p['y'], $distritos);
                $estado = ['estado' => 'berlin', 'distrito' => $d ? $nombres[$d] : null, 'x' => $p['x'], 'y' => $p['y']];
            } elseif ($p !== null) {
                $estado = ['estado' => $p['estadoPartida'] === 'en_curso' ? 'jugando' : 'esperando', 'idPartida' => $p['idPartida'],
                    'privada' => $p['tipoPartida'] === 'privada'];
            }
            return ['id' => (int) $f['IDUsuario'], 'nombre' => $f['NombreUsuario']] + $estado
                + (isset($f['XP']) ? ['nivel' => MisionLogica::nivel((int) $f['XP'])] : [])
                + (isset($f['MMR']) ? ['rango' => RankingLogica::rango((int) $f['MMR'])['icono']] : [])
                + (isset($f['Apariencia']) ? ['ap' => (int) $f['Apariencia']] : [])
                + (array_key_exists('Bando', $f) ? ['bando' => $f['Bando']] : []);
        }, $filas);
    }

    /* ---------- perfil y búsqueda ---------- */

    /* ---------- personalización del perfil (al estilo Discord) ---------- */

    public const COLOR = '/^#[0-9a-f]{6}$/';
    public const MARCOS = ['ninguno', 'bronce', 'plata', 'oro', 'muro', 'nacht', 'oeste', 'este'];

    /** Qué marcos tiene desbloqueados un jugador, y por qué (el «por qué» se muestra en la página). */
    private function marcos(int $id, array $j): array
    {
        $nivel = MisionLogica::nivel((int) $j['XP']);
        $logros = $this->social->logros($id);
        $torneos = (new FachadaPersistencia())->retornoIPersistenciaTorneos()->ganados($id);
        return [
            'ninguno' => [true, 'Sin marco'],
            'bronce' => [$nivel >= 3, 'Llegá al nivel 3'],
            'plata' => [$nivel >= 6, 'Llegá al nivel 6'],
            'oro' => [$torneos > 0, 'Ganá un torneo'],
            'muro' => [in_array('nacht_prologo', $logros, true), 'Terminá el prólogo de la campaña'],
            'nacht' => [in_array('nacht_capitulo_6', $logros, true), 'Terminá el Acto II de la campaña'],
            'oeste' => [$j['Bando'] === 'oeste', 'Sé del Oeste'],
            'este' => [$j['Bando'] === 'este', 'Sé del Este'],
        ];
    }

    /** Lo que se ve en la tarjeta de perfil: personaje (avatar), banner, acento, estado, pronombres, logros destacados y marco. */
    private function tarjetaPersonal(int $id, array $j): array
    {
        $p = $this->social->perfilPersonal($id) ?? [];
        $todos = array_column(MisionLogica::datos()['logros'], null, 'id');
        $tiene = $this->social->logros($id);
        $marcos = $this->marcos($id, $j);
        $marco = $p['Marco'] ?? 'ninguno';
        $insignias = array_values(array_filter(explode(',', (string) ($p['Insignias'] ?? '')), fn($x) => $x !== '' && in_array($x, $tiene, true) && isset($todos[$x])));
        return ['personal' => $p['Personaje'] ?? null, 'tarjeta' => [
            'banner1' => $p['Banner1'] ?? '#1b2440', 'banner2' => $p['Banner2'] ?? '#3a2a4a', 'acento' => $p['Acento'] ?? '#f4c430',
            'estadoEmoji' => $p['EstadoEmoji'] ?? '', 'estadoTexto' => $p['EstadoTexto'] ?? '', 'pronombres' => $p['Pronombres'] ?? '',
            'sobreMi' => (string) ($j['Biografia'] ?? ''),
            // Un marco que se dejó de tener (por ejemplo, al cambiar de bando) no se muestra
            'marco' => ($marcos[$marco][0] ?? false) ? $marco : 'ninguno',
            'insignias' => array_map(fn($x) => ['id' => $x, 'nombre' => $todos[$x]['nombre'], 'descripcion' => $todos[$x]['descripcion']], $insignias),
        ]];
    }

    /** Para la página «Personalizar»: lo guardado y las opciones (logros ganados y marcos con su condición). */
    public function personalizacion(int $idUsuario): array
    {
        $j = $this->social->jugador($idUsuario);
        if ($j === null) return self::error('Ese jugador no existe.');
        $todos = MisionLogica::datos()['logros'];
        $tiene = $this->social->logros($idUsuario);
        $marcos = [];
        foreach ($this->marcos($idUsuario, $j) as $id => [$ok, $como]) $marcos[] = ['id' => $id, 'desbloqueado' => $ok, 'como' => $como];
        return self::ok($this->tarjetaPersonal($idUsuario, $j) + ['nombre' => $j['NombreUsuario'], 'bando' => $j['Bando'],
            'logrosGanados' => array_values(array_map(fn($l) => ['id' => $l['id'], 'nombre' => $l['nombre']], array_filter($todos, fn($l) => in_array($l['id'], $tiene, true)))),
            'marcos' => $marcos]);
    }

    /** Guarda la tarjeta. Todo se valida: colores, largos, que el estado sea un emoji, logros propios y marcos desbloqueados. */
    public function guardarPersonalizacion(int $idUsuario, array $d): array
    {
        $j = $this->social->jugador($idUsuario);
        if ($j === null) return self::error('Ese jugador no existe.');
        $limpio = fn($s, $max) => mb_substr(trim(preg_replace('/[\p{C}<>]/u', '', (string) $s) ?? ''), 0, $max);
        $c = ['banner1' => strtolower((string) ($d['banner1'] ?? '')), 'banner2' => strtolower((string) ($d['banner2'] ?? '')), 'acento' => strtolower((string) ($d['acento'] ?? ''))];
        foreach ($c as $v) if (!preg_match(self::COLOR, $v)) return self::error('Ese color no es válido.');
        $emoji = trim((string) ($d['estadoEmoji'] ?? ''));
        // Un emoji: sin letras, números, espacios ni < > & " '; se permiten el «zero width joiner» (U+200D) y el selector U+FE0F de los emoji compuestos
        if ($emoji !== '' && (mb_strlen($emoji) > 8 || !preg_match('/^(?:[^\p{L}\p{N}\s<>&"\'\p{C}]|\x{200D}|\x{FE0F})+$/u', $emoji))) {
            return self::error('El estado lleva un solo emoji.');
        }
        $c['estadoEmoji'] = $emoji === '' ? null : $emoji;
        $c['estadoTexto'] = $limpio($d['estadoTexto'] ?? '', 80) ?: null;
        $pron = $limpio($d['pronombres'] ?? '', 24);
        if ($pron !== '' && !preg_match('/^[\p{L} \/\-]+$/u', $pron)) return self::error('Los pronombres solo llevan letras y barras (por ejemplo: él/lo).');
        $c['pronombres'] = $pron ?: null;
        $tiene = $this->social->logros($idUsuario);
        $ins = array_values(array_unique(array_filter((array) ($d['insignias'] ?? []), 'is_string')));
        if (count($ins) > 3) return self::error('Se pueden destacar hasta 3 logros.');
        foreach ($ins as $x) if (!in_array($x, $tiene, true)) return self::error('Solo podés destacar logros que ganaste.');
        $c['insignias'] = $ins;
        $marco = (string) ($d['marco'] ?? 'ninguno');
        $marcos = $this->marcos($idUsuario, $j);
        if (!isset($marcos[$marco]) || !$marcos[$marco][0]) return self::error('Ese marco todavía no está desbloqueado.');
        $c['marco'] = $marco;
        $this->social->guardarPerfilPersonal($idUsuario, $c);
        return self::ok(['mensaje' => 'Perfil guardado.']);
    }

    public function perfil(int $visitante, int $idUsuario, string $nombre = ''): array
    {
        $j = $idUsuario > 0 ? $this->social->jugador($idUsuario) : $this->social->jugadorPorNombre($nombre);
        if ($j === null || $j['Rol'] !== 'jugador') {
            return self::error('Ese jugador no existe.');
        }
        $id = (int) $j['IDUsuario'];
        $relacion = $this->relacion($visitante, $id);
        if ($relacion === 'bloqueado' && !in_array($id, array_column($this->social->bloqueados($visitante), 'IDUsuario'))) {
            return self::error('Ese jugador no existe.');   // si te bloqueó, para vos no existe
        }
        $rango = RankingLogica::rango((int) $j['MMR']);
        $todos = MisionLogica::datos()['logros'];
        $tiene = $this->social->logros($id);
        $perfil = [
            'id' => $id, 'nombre' => $j['NombreUsuario'], 'esBot' => (bool) $j['EsBot'], 'bando' => $j['Bando'], 'ap' => (int) $j['Apariencia'],
            'rango' => ['nombre' => $rango['nombre'], 'icono' => $rango['icono']], 'mmr' => (int) $j['MMR'],
            'nivel' => MisionLogica::nivel((int) $j['XP']), 'xp' => (int) $j['XP'],
            'partidas' => (int) $j['PartidasJugadas'], 'victorias' => (int) $j['PartidasGanadas'], 'puntosHistoricos' => (int) $j['PuntosHistoricos'],
            'torneosGanados' => (new FachadaPersistencia())->retornoIPersistenciaTorneos()->ganados($id),
            'logros' => array_values(array_map(fn($l) => ['nombre' => $l['nombre'], 'descripcion' => $l['descripcion']],
                array_filter($todos, fn($l) => in_array($l['id'], $tiene, true)))),
            'relacion' => $relacion,
        ] + $this->tarjetaPersonal($id, $j);
        if ($relacion === 'amigos') {
            $perfil['presencia'] = $this->conPresencia([$j])[0];
        }
        $grupo = $this->social->grupoDe($visitante);
        $perfil['enMiGrupo'] = $grupo !== null && in_array($id, array_map('intval', array_column($this->social->miembros((int) $grupo['IDGrupo']), 'IDUsuario')), true);
        return self::ok(['perfil' => $perfil]);
    }

    public function buscar(int $visitante, string $texto): array
    {
        $texto = trim($texto);
        if (mb_strlen($texto) < 2) {
            return self::ok(['jugadores' => []]);
        }
        $bloq = $this->social->idsBloqueadosEntreAmbos($visitante);
        $r = array_values(array_filter($this->social->buscar(mb_substr($texto, 0, 30), $visitante), fn($j) => !in_array((int) $j['IDUsuario'], $bloq, true)));
        return self::ok(['jugadores' => array_map(fn($j) => ['id' => (int) $j['IDUsuario'], 'nombre' => $j['NombreUsuario'],
            'nivel' => MisionLogica::nivel((int) $j['XP']), 'rango' => RankingLogica::rango((int) $j['MMR'])['icono'],
            'relacion' => $this->relacion($visitante, (int) $j['IDUsuario'])], $r)]);
    }

    /* ---------- amistades y bloqueos ---------- */

    public function solicitar(int $idUsuario, int $idOtro): array
    {
        if ($idUsuario === $idOtro) {
            return self::error('No te podés agregar a vos.');
        }
        if (!$this->esJugadorHumano($this->social->jugador($idOtro)) || $this->social->bloqueado($idUsuario, $idOtro)) {
            return self::error('No se puede agregar a ese jugador.');
        }
        $am = $this->social->amistad($idUsuario, $idOtro);
        if ($am !== null && $am['Estado'] === 'aceptada') {
            return self::error('Ya son amigos.');
        }
        if ($am !== null && (int) $am['IDSolicitante'] === $idUsuario) {
            return self::error('Ya le mandaste una solicitud.');
        }
        if ($am !== null) {   // el otro ya me había pedido: se acepta
            $this->social->aceptarAmistad($idOtro, $idUsuario);
            return self::ok(['mensaje' => 'Ahora son amigos.', 'relacion' => 'amigos']);
        }
        $this->social->pedirAmistad($idUsuario, $idOtro);
        return self::ok(['mensaje' => 'Solicitud enviada.', 'relacion' => 'enviada']);
    }

    public function responder(int $idUsuario, int $idSolicitante, bool $aceptar): array
    {
        $am = $this->social->amistad($idUsuario, $idSolicitante);
        if ($am === null || $am['Estado'] !== 'pendiente' || (int) $am['IDDestino'] !== $idUsuario) {
            return self::error('Esa solicitud ya no existe.');
        }
        if ($aceptar) {
            $this->social->aceptarAmistad($idSolicitante, $idUsuario);
            return self::ok(['mensaje' => 'Ahora son amigos.']);
        }
        $this->social->borrarAmistad($idUsuario, $idSolicitante);
        return self::ok(['mensaje' => 'Solicitud rechazada.']);
    }

    public function eliminar(int $idUsuario, int $idOtro): array
    {
        $this->social->borrarAmistad($idUsuario, $idOtro);
        return self::ok(['mensaje' => 'Listo.']);
    }

    public function bloquear(int $idUsuario, int $idOtro): array
    {
        if ($idUsuario === $idOtro || $this->social->jugador($idOtro) === null) {
            return self::error('No se puede bloquear a ese jugador.');
        }
        $this->social->iniciarTransaccion();
        try {
            $this->social->bloquear($idUsuario, $idOtro);
            $this->social->borrarAmistad($idUsuario, $idOtro);
            $this->social->borrarInvitacionesEntre($idUsuario, $idOtro);
            $this->social->confirmar();
        } catch (Throwable $e) {
            $this->social->revertir();
            throw $e;
        }
        (new AuditoriaLogica())->registrar('jugador_bloqueado', 'info', $idUsuario, null, 'Bloqueó a #' . $idOtro);
        return self::ok(['mensaje' => 'Bloqueado. Ya no se van a ver ni a escribir.']);
    }

    public function desbloquear(int $idUsuario, int $idOtro): array
    {
        $this->social->desbloquear($idUsuario, $idOtro);
        return self::ok(['mensaje' => 'Desbloqueado.']);
    }

    public function bloqueadosEntreAmbos(int $idUsuario): array
    {
        return $this->social->idsBloqueadosEntreAmbos($idUsuario);
    }

    /* ---------- panel y resumen ---------- */

    public function panel(int $idUsuario): array
    {
        $sol = $this->social->solicitudes($idUsuario);
        return self::ok([
            'amigos' => $this->conPresencia($this->social->amigos($idUsuario)),
            'recibidas' => array_values(array_map(fn($s) => ['id' => (int) $s['IDSolicitante'], 'nombre' => $s['Solicitante']],
                array_filter($sol, fn($s) => (int) $s['IDDestino'] === $idUsuario))),
            'enviadas' => array_values(array_map(fn($s) => ['id' => (int) $s['IDDestino'], 'nombre' => $s['Destino']],
                array_filter($sol, fn($s) => (int) $s['IDSolicitante'] === $idUsuario))),
            'invitaciones' => $this->invitaciones($idUsuario),
            'bloqueados' => array_map(fn($b) => ['id' => (int) $b['IDUsuario'], 'nombre' => $b['NombreUsuario']], $this->social->bloqueados($idUsuario)),
            'grupo' => $this->grupo($idUsuario)['grupo'],
        ]);
    }

    private function invitaciones(int $idUsuario): array
    {
        return array_map(fn($i) => ['id' => (int) $i['IDInvitacion'], 'tipo' => $i['Tipo'], 'de' => $i['Invitador'],
            'partida' => $i['Partida'], 'idPartida' => $i['IDPartida'] === null ? null : (int) $i['IDPartida']], $this->social->invitacionesDe($idUsuario));
    }

    /** Lo mínimo para el HUD (se pide con el estado completo del mundo). */
    public function resumen(int $idUsuario): array
    {
        $recibidas = count(array_filter($this->social->solicitudes($idUsuario), fn($s) => (int) $s['IDDestino'] === $idUsuario));
        $g = $this->social->grupoDe($idUsuario);
        return [
            'solicitudes' => $recibidas,
            'invitaciones' => $this->invitaciones($idUsuario),
            'grupo' => $g === null ? null : ['id' => (int) $g['IDGrupo'], 'lider' => (int) $g['IDLider'],
                'miembros' => array_map('intval', array_column($this->social->miembros((int) $g['IDGrupo']), 'IDUsuario'))],
        ];
    }

    /* ---------- invitaciones ---------- */

    public function invitarAMesa(int $idUsuario, int $idOtro): array
    {
        if (!$this->sonAmigos($idUsuario, $idOtro) || $this->social->bloqueado($idUsuario, $idOtro)) {
            return self::error('Solo podés invitar a tu mesa a tus amigos.');
        }
        $idPartida = (new PartidaLogica())->partidaActiva($idUsuario);
        $p = $idPartida === null ? null : $this->social->partidaVisible($idPartida);
        if ($p === null || $p['Estado'] !== 'esperando') {
            return self::error('Para invitar tenés que estar en una mesa que todavía no empezó.');
        }
        $this->social->invitar('mesa', $idUsuario, $idOtro, null, $idPartida);
        return self::ok(['mensaje' => 'Invitación enviada.']);
    }

    public function invitarAGrupo(int $idUsuario, int $idOtro): array
    {
        if ($idUsuario === $idOtro || !$this->esJugadorHumano($this->social->jugador($idOtro)) || $this->social->bloqueado($idUsuario, $idOtro)) {
            return self::error('No se puede invitar a ese jugador.');
        }
        $this->social->iniciarTransaccion();
        try {
            $g = $this->social->grupoDe($idUsuario);
            $idGrupo = $g === null ? $this->social->crearGrupo($idUsuario) : (int) $g['IDGrupo'];
            $this->social->bloquearGrupo($idGrupo);
            $miembros = array_map('intval', array_column($this->social->miembros($idGrupo), 'IDUsuario'));
            if (in_array($idOtro, $miembros, true)) {
                $this->social->revertir();
                return self::error('Ya está en tu grupo.');
            }
            if (count($miembros) >= self::MAX_GRUPO) {
                $this->social->revertir();
                return self::error('El grupo ya tiene ' . self::MAX_GRUPO . ' integrantes.');
            }
            $this->social->invitar('grupo', $idUsuario, $idOtro, $idGrupo, null);
            $this->social->confirmar();
        } catch (Throwable $e) {
            $this->social->revertir();
            throw $e;
        }
        return self::ok(['mensaje' => 'Invitación al grupo enviada.']);
    }

    public function aceptarInvitacion(int $idUsuario, int $idInvitacion): array
    {
        $i = $this->social->invitacion($idInvitacion);
        if ($i === null || (int) $i['IDInvitado'] !== $idUsuario) {
            return self::error('Esa invitación ya no existe.');
        }
        if ($this->social->bloqueado($idUsuario, (int) $i['IDInvitador'])) {
            $this->social->borrarInvitacion($idInvitacion);
            return self::error('Esa invitación ya no existe.');
        }
        if ($i['Tipo'] === 'mesa') {
            $r = (new PartidaLogica())->unirse($idUsuario, (int) $i['IDPartida'], '', true);
            if ($r['exito']) {
                $this->social->borrarInvitacion($idInvitacion);
            }
            return $r;
        }
        $this->social->iniciarTransaccion();
        try {
            $idGrupo = (int) $i['IDGrupo'];
            if ($this->social->bloquearGrupo($idGrupo) === null) {
                $this->social->revertir();
                $this->social->borrarInvitacion($idInvitacion);
                return self::error('Ese grupo ya no existe.');
            }
            if (count($this->social->miembros($idGrupo)) >= self::MAX_GRUPO) {
                $this->social->revertir();
                return self::error('El grupo ya está completo.');
            }
            $this->salirSinTransaccion($idUsuario);   // si estaba en otro grupo, lo deja
            $this->social->agregarMiembro($idGrupo, $idUsuario);
            $this->social->borrarInvitacion($idInvitacion);
            $this->social->confirmar();
        } catch (Throwable $e) {
            $this->social->revertir();
            throw $e;
        }
        return self::ok(['mensaje' => 'Te sumaste al grupo.']);
    }

    public function rechazarInvitacion(int $idUsuario, int $idInvitacion): array
    {
        $i = $this->social->invitacion($idInvitacion);
        if ($i !== null && (int) $i['IDInvitado'] === $idUsuario) {
            $this->social->borrarInvitacion($idInvitacion);
        }
        return self::ok();
    }

    /** Cuando el líder arma una mesa en un café, se invita a todo su grupo. */
    public function avisarAlGrupo(int $idLider, int $idPartida): void
    {
        $g = $this->social->grupoDe($idLider);
        if ($g === null || (int) $g['IDLider'] !== $idLider) {
            return;
        }
        foreach ($this->social->miembros((int) $g['IDGrupo']) as $m) {
            if ((int) $m['IDUsuario'] !== $idLider) {
                $this->social->invitar('mesa', $idLider, (int) $m['IDUsuario'], null, $idPartida);
            }
        }
    }

    /* ---------- grupos ---------- */

    private function salirSinTransaccion(int $idUsuario): void
    {
        $g = $this->social->grupoDe($idUsuario);
        if ($g === null) {
            return;
        }
        $idGrupo = (int) $g['IDGrupo'];
        $this->social->quitarMiembro($idUsuario);
        $quedan = $this->social->miembros($idGrupo);
        if (count($quedan) <= 1) {
            $this->social->borrarGrupo($idGrupo);   // un grupo de uno no es un grupo
        } elseif ((int) $g['IDLider'] === $idUsuario) {
            $this->social->cambiarLider($idGrupo, (int) $quedan[0]['IDUsuario']);   // el que hace más que está
        }
    }

    public function salirDelGrupo(int $idUsuario): array
    {
        $this->social->iniciarTransaccion();
        try {
            $g = $this->social->grupoDe($idUsuario);
            if ($g !== null) {
                $this->social->bloquearGrupo((int) $g['IDGrupo']);
            }
            $this->salirSinTransaccion($idUsuario);
            $this->social->confirmar();
        } catch (Throwable $e) {
            $this->social->revertir();
            throw $e;
        }
        return self::ok(['mensaje' => 'Saliste del grupo.']);
    }

    public function expulsar(int $idLider, int $idOtro): array
    {
        $g = $this->social->grupoDe($idLider);
        $go = $this->social->grupoDe($idOtro);
        if ($g === null || (int) $g['IDLider'] !== $idLider || $go === null || (int) $go['IDGrupo'] !== (int) $g['IDGrupo'] || $idLider === $idOtro) {
            return self::error('Solo el líder puede sacar a alguien de su grupo.');
        }
        return $this->salirDelGrupo($idOtro);
    }

    public function grupo(int $idUsuario, int $desde = 0): array
    {
        $g = $this->social->grupoDe($idUsuario);
        if ($g === null) {
            return self::ok(['grupo' => null, 'mensajes' => []]);
        }
        $idGrupo = (int) $g['IDGrupo'];
        $miembros = $this->conPresencia($this->social->miembros($idGrupo));
        foreach ($miembros as &$m) {
            $m['lider'] = $m['id'] === (int) $g['IDLider'];
        }
        unset($m);
        return self::ok([
            'grupo' => ['id' => $idGrupo, 'soyLider' => (int) $g['IDLider'] === $idUsuario, 'miembros' => $miembros, 'max' => self::MAX_GRUPO],
            'mensajes' => array_map(fn($m) => ['id' => (int) $m['IDMensaje'], 'de' => $m['NombreUsuario'], 'idDe' => (int) $m['IDUsuario'], 'texto' => $m['Texto']],
                $this->social->mensajesGrupo($idGrupo, $desde)),
        ]);
    }

    public function decirAlGrupo(int $idUsuario, string $texto): array
    {
        $texto = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $texto) ?? '');
        if ($texto === '' || mb_strlen($texto) > 200) {
            return self::error('El mensaje tiene que tener entre 1 y 200 caracteres.');
        }
        $g = $this->social->grupoDe($idUsuario);
        if ($g === null) {
            return self::error('No estás en un grupo.');
        }
        $this->social->decirGrupo((int) $g['IDGrupo'], $idUsuario, $texto);
        return self::ok();
    }

    /* ---------- mirar partidas ---------- */

    /** Partidas públicas en curso en el café de un distrito (para mirarlas). */
    public function partidasParaMirar(int $idDistrito): array
    {
        return array_map(fn($p) => ['id' => (int) $p['IDPartida'], 'nombre' => $p['Nombre'], 'jugadores' => (int) $p['Jugadores']],
            $this->social->partidasEnCursoDeDistrito($idDistrito));
    }

    public function puedeMirar(int $idUsuario, int $idPartida): array
    {
        $p = $this->social->partidaVisible($idPartida);
        if ($p === null || !in_array($p['Estado'], ['en_curso', 'esperando', 'finalizada'], true)) {
            return self::error('Esa partida no existe.');
        }
        if ($p['Tipo'] === 'privada') {
            $amigo = false;
            foreach ($p['jugadores'] as $j) {
                $amigo = $amigo || $this->sonAmigos($idUsuario, $j);
            }
            if (!$amigo) {
                return self::error('Esa partida es privada: solo la pueden mirar los amigos de sus jugadores.');
            }
        }
        foreach ($p['jugadores'] as $j) {
            if ($this->social->bloqueado($idUsuario, $j)) {
                return self::error('No podés mirar esa partida.');
            }
        }
        return self::ok();
    }
}
?>
