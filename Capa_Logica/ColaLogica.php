<?php
require_once(__DIR__ . '/IColaLogica.php');
require_once(__DIR__ . '/PartidaLogica.php');
require_once(__DIR__ . '/RankingLogica.php');
require_once(__DIR__ . '/../Capa_Persistencia/FachadaPersistencia.php');

/*
 * Emparejamiento del modo competitivo.
 *
 * Se busca armar mesas de 4 con MMR parecido. La ventana de MMR aceptable
 * arranca en ±150 y se agranda 10 puntos por segundo de espera, así nadie
 * espera para siempre. Si alguien lleva 60 s y hay 2 o 3 jugadores
 * compatibles, se arma con esos. Sin bots y con reglas oficiales fijas.
 *
 * Igual que la partida, se resuelve "a demanda": cada consulta de alguien
 * que está en la cola intenta emparejar.
 */
class ColaLogica implements IColaLogica
{
    public const TAMANO_IDEAL = 4;
    public const SEGUNDOS_PARA_MESA_CHICA = 60;
    public const VENTANA_INICIAL = 150;
    public const VENTANA_POR_SEGUNDO = 10;
    public const SEGUNDOS_SIN_LATIDO = 20;

    private $cola;
    private PartidaLogica $partidas;

    public function __construct()
    {
        $this->cola = (new FachadaPersistencia())->retornoIPersistenciaCola();
        $this->partidas = new PartidaLogica();
    }

    public function entrar(int $idUsuario): array
    {
        $activa = $this->partidas->partidaActiva($idUsuario);
        if ($activa !== null) {
            return ['exito' => false, 'mensaje' => 'Ya estás en una partida.', 'idPartida' => $activa];
        }
        $this->cola->entrar($idUsuario);
        return ['exito' => true];
    }

    public function salir(int $idUsuario): void
    {
        $this->cola->salir($idUsuario);
    }

    public function estado(int $idUsuario): array
    {
        $this->cola->latido($idUsuario);
        $this->emparejar();
        $fila = $this->cola->filaDe($idUsuario);
        if ($fila === null) {
            return ['enCola' => false];
        }
        if ($fila['IDPartidaAsignada'] !== null) {
            $this->cola->salir($idUsuario);
            return ['enCola' => false, 'idPartida' => (int) $fila['IDPartidaAsignada']];
        }
        return [
            'enCola' => true,
            'segundos' => (int) $fila['Segundos'],
            'esperando' => $this->cola->cantidadEsperando(),
            'ventana' => self::VENTANA_INICIAL + self::VENTANA_POR_SEGUNDO * (int) $fila['Segundos'],
        ];
    }

    /** Arma todas las mesas que se puedan con la gente que está esperando ahora. */
    private function emparejar(): void
    {
        $this->cola->iniciarTransaccion();
        try {
            $this->cola->limpiarInactivos(self::SEGUNDOS_SIN_LATIDO);
            $esperando = $this->cola->esperandoBloqueados();
            while (($mesa = self::elegirMesa($esperando)) !== null) {
                $idPartida = $this->partidas->crearCompetitiva($mesa);
                $this->cola->asignar($mesa, $idPartida);
                $esperando = array_values(array_filter($esperando, fn($j) => !in_array($j['id'], $mesa, true)));
            }
            $this->cola->confirmar();
        } catch (Throwable $e) {
            $this->cola->revertir();
            error_log('Error al emparejar la cola competitiva: ' . $e->getMessage());
        }
    }

    /**
     * Elige la próxima mesa (lista de IDUsuario) o null si todavía no hay.
     * Pública y estática para poder probarla sin base de datos.
     */
    public static function elegirMesa(array $esperando): ?array
    {
        foreach ($esperando as $ancla) {
            $ventana = self::VENTANA_INICIAL + self::VENTANA_POR_SEGUNDO * $ancla['segundos'];
            $compatibles = array_values(array_filter($esperando, fn($j) => $j['id'] !== $ancla['id']
                && abs($j['mmr'] - $ancla['mmr']) <= $ventana));
            usort($compatibles, fn($a, $b) => abs($a['mmr'] - $ancla['mmr']) <=> abs($b['mmr'] - $ancla['mmr']));
            if (count($compatibles) >= self::TAMANO_IDEAL - 1) {
                return array_merge([$ancla['id']], array_column(array_slice($compatibles, 0, self::TAMANO_IDEAL - 1), 'id'));
            }
            if ($ancla['segundos'] >= self::SEGUNDOS_PARA_MESA_CHICA && count($compatibles) >= 1) {
                return array_merge([$ancla['id']], array_column($compatibles, 'id'));
            }
        }
        return null;
    }
}
?>
