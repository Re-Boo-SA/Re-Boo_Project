<?php
require_once(__DIR__ . '/MundoLogica.php');
require_once(__DIR__ . '/SucesoLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * Diario del día (Berlín vivo, etapa B). Dos diarios ficticios, uno de cada lado del Muro,
 * cuentan los mismos hechos del juego con distinto tono. Cada titular es una PLANTILLA con
 * valores, así el navegador lo traduce (los valores de "traducir" también pasan por el diccionario).
 */
class DiarioLogica
{
    public const NOMBRES = ['oeste' => 'Der Berliner Abendbote', 'este' => 'Stimme der Hauptstadt'];

    private static function titular(string $plantilla, array $valores = [], array $traducir = [], string $bajada = '', array $valoresBajada = [], array $traducirBajada = [], ?string $prefijo = null): array
    {
        return ['prefijo' => $prefijo, 'plantilla' => $plantilla, 'valores' => (object) $valores, 'traducir' => $traducir,
            'bajada' => $bajada, 'valoresBajada' => (object) $valoresBajada, 'traducirBajada' => $traducirBajada];
    }

    public function edicion(string $lado): array
    {
        $lado = $lado === 'este' ? 'este' : 'oeste';
        $mundo = new MundoLogica();
        $estado = $mundo->estado(0);
        $oeste = count(array_filter($estado['dominio'], fn($d) => ($d['domina'] ?? null) === 'oeste'));
        $este = count(array_filter($estado['dominio'], fn($d) => ($d['domina'] ?? null) === 'este'));
        $nuestros = $lado === 'oeste' ? $oeste : $este;
        $ellos = $lado === 'oeste' ? $este : $oeste;
        $t = [];

        // 1) La conquista, contada por cada lado
        if ($nuestros > $ellos) {
            $t[] = $lado === 'oeste'
                ? self::titular('El Oeste se impone en {n} de los 12 distritos', ['n' => $nuestros], [], 'Los cafés del sector occidental no dan abasto.')
                : self::titular('Los trabajadores del Este sostienen {n} distritos', ['n' => $nuestros], [], 'La planificación da sus frutos en cada café de la capital.');
        } elseif ($nuestros < $ellos) {
            $t[] = $lado === 'oeste'
                ? self::titular('Preocupación: el Este controla {n} distritos', ['n' => $ellos], [], 'Se pide a los vecinos del Oeste que vuelvan a las mesas.')
                : self::titular('Ofensiva occidental en {n} distritos: la capital responde', ['n' => $ellos], [], 'Los cafés del Este llaman a redoblar el esfuerzo.');
        } else {
            $t[] = self::titular('Empate en la ciudad dividida: {n} distritos por lado', ['n' => $nuestros], [], 'Ningún bando logra sacar ventaja.');
        }

        // 2) El suceso del momento y el que viene
        $suceso = SucesoLogica::actual();
        if ($suceso !== null) {
            $t[] = self::titular($suceso['titulo'], ['distrito' => (string) $suceso['nombreDistrito']], [], $suceso['descripcion'], [], [], 'Hoy');
        }
        $proximo = SucesoLogica::proximo();
        if ($proximo !== null) {
            $t[] = self::titular($proximo['titulo'], ['distrito' => (string) $proximo['nombreDistrito']], [], '', [], [], 'Más tarde');
        }

        // 3) Torneos y figura del día
        $sucesos = (new FachadaPersistencia())->retornoIPersistenciaSucesos();
        if (($c = $sucesos->ultimoCampeon()) !== null) {
            $t[] = self::titular('{campeon} gana el torneo «{torneo}»', ['campeon' => $c['Campeon'], 'torneo' => $c['Nombre']], [], 'La Torre de TV transmitió la final.');
        }
        if (($f = $sucesos->figuraDelDia()) !== null) {
            $t[] = self::titular('Figura del día: {jugador}, con {puntos} puntos', ['jugador' => $f['NombreUsuario'], 'puntos' => (int) $f['Puntos']]);
        }

        // 4) Clima y temporada
        $reloj = MundoLogica::reloj(microtime(true));
        $nombres = ['despejado' => 'Despejado', 'nublado' => 'Nublado', 'lluvia' => 'Lluvia', 'niebla' => 'Niebla', 'nieve' => 'Nieve'];
        $t[] = self::titular('Pronóstico: {clima}', ['clima' => $nombres[$reloj['climaSiguiente']]], ['clima']);
        $dias = intdiv(max(0, (int) $estado['temporada']['segundosRestantes']), 86400);
        $t[] = self::titular('Quedan {dias} días de la temporada {n}', ['dias' => $dias, 'n' => (int) $estado['temporada']['numero']]);

        return ['exito' => true, 'lado' => $lado, 'nombre' => self::NOMBRES[$lado],
            // El presente del juego es Berlín a mediados de los 70 (ver docs/campana.md)
            'fecha' => ['dia' => (int) date('j'), 'mes' => (int) date('n'), 'anio' => 1975],
            'titulares' => $t];
    }
}
?>
