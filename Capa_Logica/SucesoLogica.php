<?php
require_once(__DIR__ . '/MundoLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * ======================================================================
 *  SUCESOS (Berlín vivo, etapa B)
 *
 *  Se sortean como el clima: por tramos de 10 minutos y con la hora del servidor,
 *  así que todos viven el mismo suceso a la vez y no hace falta ningún proceso aparte.
 *  El contenido está en datos/mundo/sucesos.json. Efectos que valida el servidor:
 *   - control_checkpoint: se cruza Checkpoint Charlie más lento (MundoLogica::movimientoValido);
 *   - torneo_cafe: las partidas de ese café valen doble influencia (MundoLogica::registrarPartida);
 *   - mercado: visitar al vendedor da XP una sola vez por suceso.
 *  El resto (apagón, manifestación, concierto, visita oficial) cambia lo que se ve y lo que
 *  dicen los contactos.
 * ======================================================================
 */
class SucesoLogica
{
    /** Zona del paso de Checkpoint Charlie donde rige el control. */
    public const ZONA_PASO = ['x0' => 1040, 'y0' => 860, 'x1' => 1440, 'y1' => 1020];
    public const DISTANCIA_VISITA = 120;
    /** Solo para pruebas: ['tipo' => ..., 'distrito' => id|null] fija el suceso actual. */
    public static ?array $sucesoFijo = null;

    private static ?array $datos = null;
    private static ?array $distritos = null;

    /**
     * Suceso fijo para pruebas: el de los tests ($sucesoFijo) o, en un servidor de prueba, la variable
     * de entorno REBOO_SUCESO_FIJO="tipo:distrito" (por ejemplo "manifestacion:6"). Nunca desde un pedido web.
     */
    private static function fijo(): ?array
    {
        if (self::$sucesoFijo !== null) {
            return self::$sucesoFijo;
        }
        $env = getenv('REBOO_SUCESO_FIJO');
        if (!is_string($env) || $env === '') {
            return null;
        }
        [$tipo, $distrito] = array_pad(explode(':', $env, 2), 2, null);
        return ['tipo' => $tipo, 'distrito' => $distrito === null ? null : (int) $distrito];
    }

    public static function datos(): array
    {
        if (self::$datos === null) {
            $j = json_decode((string) @file_get_contents(dirname(__DIR__) . '/datos/mundo/sucesos.json'), true);
            if (!is_array($j)) {
                throw new RuntimeException('Falta o está mal escrito datos/mundo/sucesos.json.');
            }
            $j['porTipo'] = array_column($j['tipos'], null, 'id');
            self::$datos = $j;
        }
        return self::$datos;
    }

    private static function distritos(): array
    {
        return self::$distritos ??= (new FachadaPersistencia())->retornoIPersistenciaMundo()->distritos();
    }

    public static function tramo(?float $t = null): int
    {
        return intdiv((int) ($t ?? microtime(true)), (int) self::datos()['duracionTramo']);
    }

    /** Solo el tipo del suceso de un tramo (sin tocar la base: se usa en cada paso del jugador). */
    public static function tipoDeTramo(int $tramo): ?string
    {
        if (self::fijo() !== null && $tramo === self::tramo()) {
            return self::fijo()['tipo'];
        }
        $d = self::datos();
        $h = crc32('suceso-berlin-' . $tramo);
        if ($h % 100 >= $d['probabilidad']) {
            return null;
        }
        $total = array_sum(array_column($d['tipos'], 'peso'));
        $tirada = intdiv($h, 100) % $total;
        foreach ($d['tipos'] as $tipo) {
            $tirada -= $tipo['peso'];
            if ($tirada < 0) {
                return $tipo['id'];
            }
        }
        return null;
    }

    /** El suceso completo de un tramo: dónde, qué distrito y sus efectos. */
    public static function deTramo(int $tramo): ?array
    {
        $tipo = self::tipoDeTramo($tramo);
        if ($tipo === null) {
            return null;
        }
        $def = self::datos()['porTipo'][$tipo];
        $distrito = null;
        $permitidos = $def['distritos'] ?? null;
        if ($permitidos !== null) {
            $candidatos = array_values(array_filter(self::distritos(), fn($d) => $permitidos === 'todos'
                || (is_string($permitidos) && $d['Lado'] === $permitidos) || (is_array($permitidos) && in_array((int) $d['IDDistrito'], $permitidos, true))));
            $distrito = self::fijo() !== null && $tramo === self::tramo() && isset(self::fijo()['distrito'])
                ? self::fijo()['distrito']
                : (int) $candidatos[intdiv(crc32('suceso-berlin-' . $tramo), 10000) % count($candidatos)]['IDDistrito'];
        }
        $d = $distrito === null ? null : array_values(array_filter(self::distritos(), fn($x) => (int) $x['IDDistrito'] === $distrito))[0];
        [$x, $y] = $def['lugar'] ?? self::lugarEnDistrito($distrito);
        $suceso = [
            'tramo' => $tramo, 'tipo' => $tipo, 'titulo' => $def['titulo'], 'descripcion' => $def['descripcion'], 'icono' => $def['icono'] ?? '',
            'distrito' => $distrito, 'nombreDistrito' => $d['Nombre'] ?? null, 'x' => $x, 'y' => $y, 'efecto' => $def['efecto'] ?? new stdClass(),
        ];
        if ($tipo === 'visita_oficial' && $d !== null) {
            // La caravana cruza el distrito por la avenida más cercana a su centro
            $centro = (int) $d['Y'] + intdiv((int) $d['Alto'], 2);
            $calles = [160, 560, 940, 1280];
            usort($calles, fn($a, $b) => abs($a - $centro) <=> abs($b - $centro));
            $suceso['ruta'] = [[(int) $d['X'] + 20, $calles[0]], [(int) $d['X'] + (int) $d['Ancho'] - 20, $calles[0]]];
        }
        return $suceso;
    }

    /** Frente al café del distrito (allí se arman el mercado, la manifestación o la noche de torneo). */
    private static function lugarEnDistrito(?int $distrito): array
    {
        foreach (MundoLogica::mapaTiled()['edificios'] as $e) {
            if ($e['tipo'] === 'cafe' && (int) $e['distrito'] === $distrito) {
                return [$e['puertaX'], $e['puertaY'] + 50];
            }
        }
        return [1200, 700];
    }

    public static function actual(?float $t = null): ?array
    {
        $t ??= microtime(true);
        $s = self::deTramo(self::tramo($t));
        if ($s !== null) {
            $s['segundosRestantes'] = (int) self::datos()['duracionTramo'] - ((int) $t % (int) self::datos()['duracionTramo']);
        }
        return $s;
    }

    public static function proximo(?float $t = null): ?array
    {
        return self::deTramo(self::tramo($t) + 1);
    }

    /** Factor de velocidad para un paso: más lento dentro del paso de Checkpoint Charlie si hay control. */
    public static function factorVelocidad(int $x1, int $y1, int $x2, int $y2): float
    {
        if (self::tipoDeTramo(self::tramo()) !== 'control_checkpoint') {
            return 1.0;
        }
        $z = self::ZONA_PASO;
        $dentro = fn($x, $y) => $x >= $z['x0'] && $x <= $z['x1'] && $y >= $z['y0'] && $y <= $z['y1'];
        return $dentro($x1, $y1) || $dentro($x2, $y2) ? (float) self::datos()['porTipo']['control_checkpoint']['efecto']['velocidadPaso'] : 1.0;
    }

    /** Multiplicador de influencia para una partida jugada en el café de $idDistrito. */
    public static function multiplicadorInfluencia(?int $idDistrito): int
    {
        if ($idDistrito === null || self::tipoDeTramo(self::tramo()) !== 'torneo_cafe') {
            return 1;
        }
        $s = self::actual();
        return $s['distrito'] === $idDistrito ? (int) $s['efecto']['influencia'] : 1;
    }

    /** Visitar el mercado: XP una sola vez por suceso, estando cerca del vendedor. */
    public function visitar(int $idUsuario): array
    {
        $s = self::actual();
        if ($s === null || empty($s['efecto']['xpVisita'])) {
            return ['exito' => false, 'mensaje' => 'Ahora no hay nada para visitar.'];
        }
        $f = new FachadaPersistencia();
        $p = $f->retornoIPersistenciaMundo()->posicion($idUsuario);
        if ($p === null || hypot($p['X'] - $s['x'], $p['Y'] - $s['y']) > self::DISTANCIA_VISITA) {
            return ['exito' => false, 'mensaje' => 'Acercate a los puestos.'];
        }
        if (!$f->retornoIPersistenciaSucesos()->cobrarPremio($idUsuario, $s['tramo'])) {
            return ['exito' => false, 'mensaje' => 'Ya diste una vuelta por este mercado.'];
        }
        $f->retornoIPersistenciaMisiones()->sumarRecompensa($idUsuario, (int) $s['efecto']['xpVisita'], 0);
        return ['exito' => true, 'mensaje' => 'Diste una vuelta por el mercado.', 'xp' => (int) $s['efecto']['xpVisita']];
    }
}
?>
