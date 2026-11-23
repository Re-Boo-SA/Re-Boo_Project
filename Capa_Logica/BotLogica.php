<?php
require_once(__DIR__ . '/MotorJuego.php');

/*
 * Bots: usan el mismo motor que los humanos (no pueden hacer trampa)
 * y eligen con MotorJuego::mejorColocacion. Cada uno tiene su estilo:
 * un poco de azar ("ruido") y preferencia por ciertos recintos.
 */
class BotLogica
{
    private const PERSONALIDADES = [
        'Bot Klaus' => ['ruido' => 0.6, 'preferencias' => ['oeste' => 0.4, 'este' => 0.4, 'muro' => 0.7]],
        'Bot Greta' => ['ruido' => 0.3, 'preferencias' => ['espias' => 1.2]],
        'Bot Dieter' => ['ruido' => 0.3, 'preferencias' => ['militares' => 1.0, 'fabrica' => 0.4]],
        'Bot Ilse' => ['ruido' => 0.25, 'preferencias' => ['politicos' => 1.5, 'fabrica' => 0.5]],
        // Bots de los contactos (etapa C): solo juegan los desafíos de sus misiones.
        'Ingrid Vogel' => ['ruido' => 0.35, 'preferencias' => ['oeste' => 0.6, 'este' => 0.6, 'muro' => 0.5]],
        'Paul Lange' => ['ruido' => 0.3, 'preferencias' => ['militares' => 1.2, 'fabrica' => 0.8]],
        'Gertrud Hartmann' => ['ruido' => 0.15, 'preferencias' => ['politicos' => 1.6, 'muro' => -0.8]],
        'Lotte' => ['ruido' => 0.2, 'preferencias' => ['espias' => 1.6, 'politicos' => 0.4]],
        'Otto Weiss' => ['ruido' => 0.55, 'preferencias' => ['oeste' => 0.4, 'fabrica' => 0.6]],   // Cap. 1: juega nervioso
    ];

    private const POR_DEFECTO = ['ruido' => 0.4, 'preferencias' => []];

    /** Segundos que "piensa" un bot antes de jugar, para que se vea natural. */
    public static function demora(int $asiento): int
    {
        return 2 + $asiento % 3;
    }

    public static function elegirColocacion(MotorJuego $motor, string $nombreBot, array $tablero, array $mano, ?string $cara, bool $esTirador, int $ronda, int $turno): ?array
    {
        $estilo = self::PERSONALIDADES[$nombreBot] ?? self::POR_DEFECTO;
        return $motor->mejorColocacion($tablero, $mano, $cara, $esTirador, $ronda, $turno, $estilo['ruido'], $estilo['preferencias']);
    }

    public static function elegirMovimiento(MotorJuego $motor, array $tablero, int $ronda, int $turno): ?array
    {
        return $motor->mejorMovimiento($tablero, $ronda, $turno);
    }
}
?>
