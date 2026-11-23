<?php
require_once(__DIR__ . '/IRankingLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * Ranking global y MMR del modo competitivo.
 *
 * MMR = Elo multijugador: cada partida se piensa como un "uno contra uno"
 * de cada jugador contra cada rival. Ganarle a alguien de MMR más alto
 * suma más; perder contra alguien más bajo resta más. Abandonar cuenta
 * como último puesto y además resta una penalización.
 */
class RankingLogica implements IRankingLogica
{
    public const MMR_INICIAL = 1000;
    public const FACTOR_K = 32;
    public const PENALIZACION_ABANDONO = 15;

    /** Rangos por MMR mínimo, de mayor a menor. */
    public const RANGOS = [
        ['minimo' => 1700, 'nombre' => 'General', 'icono' => '★★★'],
        ['minimo' => 1500, 'nombre' => 'Coronel', 'icono' => '★★'],
        ['minimo' => 1350, 'nombre' => 'Capitán', 'icono' => '★'],
        ['minimo' => 1200, 'nombre' => 'Teniente', 'icono' => '▲▲▲'],
        ['minimo' => 1050, 'nombre' => 'Sargento', 'icono' => '▲▲'],
        ['minimo' => 900, 'nombre' => 'Cabo', 'icono' => '▲'],
        ['minimo' => 0, 'nombre' => 'Recluta', 'icono' => '•'],
    ];

    public const ORDENES = ['mmr', 'historicos', 'victorias', 'porcentaje'];

    public static function rango(int $mmr): array
    {
        foreach (self::RANGOS as $rango) {
            if ($mmr >= $rango['minimo']) {
                return $rango;
            }
        }
        return end(self::RANGOS);
    }

    /**
     * Calcula el MMR nuevo de cada jugador.
     * $jugadores = [id => ['mmr' => int, 'posicion' => ?int]]; posicion null = abandonó.
     * Devuelve [id => mmrNuevo].
     */
    public static function calcularMMR(array $jugadores): array
    {
        $ultimo = count($jugadores) + 1;
        $ids = array_keys($jugadores);
        $nuevos = [];
        foreach ($ids as $i) {
            $delta = 0.0;
            $posI = $jugadores[$i]['posicion'] ?? $ultimo;
            foreach ($ids as $j) {
                if ($i === $j) {
                    continue;
                }
                $posJ = $jugadores[$j]['posicion'] ?? $ultimo;
                $esperado = 1 / (1 + 10 ** (($jugadores[$j]['mmr'] - $jugadores[$i]['mmr']) / 400));
                $real = $posI < $posJ ? 1.0 : ($posI === $posJ ? 0.5 : 0.0);
                $delta += $real - $esperado;
            }
            $delta = self::FACTOR_K * $delta / max(1, count($ids) - 1);
            if ($jugadores[$i]['posicion'] === null) {
                $delta -= self::PENALIZACION_ABANDONO;
            }
            $nuevos[$i] = max(0, (int) round($jugadores[$i]['mmr'] + $delta));
        }
        return $nuevos;
    }

    public function tabla(string $orden, int $idUsuario): array
    {
        if (!in_array($orden, self::ORDENES, true)) {
            $orden = 'mmr';
        }
        $persistencia = (new FachadaPersistencia())->retornoIPersistenciaRanking();
        $filas = $persistencia->top($orden, 100);
        foreach ($filas as &$fila) {
            $fila['rango'] = self::rango((int) $fila['MMR']);
        }
        unset($fila);
        $mia = $persistencia->posicionDe($orden, $idUsuario);
        if ($mia !== null) {
            $mia['rango'] = self::rango((int) $mia['MMR']);
        }
        return ['filas' => $filas, 'mia' => $mia, 'orden' => $orden];
    }
}
?>
