<?php
/*
 * Reglas con las que se juega una partida (tabla CONFIG_PARTIDA).
 * Los valores por defecto son las reglas oficiales del manual: así se
 * juegan las partidas normales y las competitivas. Las personalizadas
 * pueden cambiar cualquiera de estos valores dentro de los rangos.
 */
class ConfigPartida
{
    // Rangos permitidos (los mismos CHECK que tiene la base de datos).
    public const RANGOS = [
        'rondas' => [1, 3],
        'fichasPorRonda' => [4, 8],
        'capacidadMuro' => [1, 24],
        'segundosTurno' => [15, 300],
        'valorTipo1' => [0, 20],
        'valorTipo2' => [0, 20],
        'valorTipo3' => [0, 20],
        'puntosEspias' => [0, 20],
    ];

    public const TODAS_LAS_CARAS = [
        'lado_izquierdo', 'lado_derecho', 'solo_militares',
        'bloquea_fabrica', 'cambiar_ficha', 'solo_espias'
    ];

    private int $rondas = 2;
    private int $fichasPorRonda = 6;
    private int $capacidadMuro = 12;
    private int $segundosTurno = 60;
    private int $valorTipo1 = 1;
    private int $valorTipo2 = 3;
    private int $valorTipo3 = 7;
    private int $puntosEspias = 4;
    private bool $reglaPolitico = true;
    private bool $tiradorExento = true;
    private bool $permiteBots = false;
    /** @var string[] códigos de las caras habilitadas */
    private array $caras = self::TODAS_LAS_CARAS;

    /** Crea la configuración oficial (la de una partida normal). */
    public static function oficial(): self
    {
        return new self();
    }

    /**
     * Crea una configuración a partir de un arreglo (por ejemplo, una fila
     * de la base o el formulario de partida personalizada). Las claves que
     * no vienen quedan con el valor oficial.
     */
    public static function desdeArreglo(array $datos): self
    {
        $config = new self();
        foreach (array_keys(self::RANGOS) as $clave) {
            if (isset($datos[$clave])) {
                $config->$clave = (int) $datos[$clave];
            }
        }
        foreach (['reglaPolitico', 'tiradorExento', 'permiteBots'] as $clave) {
            if (isset($datos[$clave])) {
                $config->$clave = (bool) $datos[$clave];
            }
        }
        if (isset($datos['caras']) && is_array($datos['caras'])) {
            $config->caras = array_values(array_intersect(self::TODAS_LAS_CARAS, $datos['caras']));
        }
        return $config;
    }

    /** Devuelve el primer error encontrado o null si todo está en rango. */
    public function validar(): ?string
    {
        foreach (self::RANGOS as $clave => [$minimo, $maximo]) {
            if ($this->$clave < $minimo || $this->$clave > $maximo) {
                return "El valor de \"$clave\" tiene que estar entre $minimo y $maximo.";
            }
        }
        if ($this->caras === []) {
            return 'Tiene que quedar al menos una cara del dado habilitada.';
        }
        return null;
    }

    public function aArreglo(): array
    {
        return [
            'rondas' => $this->rondas,
            'fichasPorRonda' => $this->fichasPorRonda,
            'capacidadMuro' => $this->capacidadMuro,
            'segundosTurno' => $this->segundosTurno,
            'valorTipo1' => $this->valorTipo1,
            'valorTipo2' => $this->valorTipo2,
            'valorTipo3' => $this->valorTipo3,
            'puntosEspias' => $this->puntosEspias,
            'reglaPolitico' => $this->reglaPolitico,
            'tiradorExento' => $this->tiradorExento,
            'permiteBots' => $this->permiteBots,
            'caras' => $this->caras,
        ];
    }

    /** ¿Es idéntica a las reglas oficiales? (sin contar bots) */
    public function esOficial(): bool
    {
        $mia = $this->aArreglo();
        $oficial = self::oficial()->aArreglo();
        unset($mia['permiteBots'], $oficial['permiteBots']);
        sort($mia['caras']);
        sort($oficial['caras']);
        return $mia == $oficial;
    }

    public function getRondas(): int { return $this->rondas; }
    public function getFichasPorRonda(): int { return $this->fichasPorRonda; }
    public function getCapacidadMuro(): int { return $this->capacidadMuro; }
    public function getSegundosTurno(): int { return $this->segundosTurno; }
    /** Valores 1/3/7 de Fábrica y Militares (por orden de aparición del tipo). */
    public function getValoresTipos(): array { return [$this->valorTipo1, $this->valorTipo2, $this->valorTipo3]; }
    public function getPuntosEspias(): int { return $this->puntosEspias; }
    public function getReglaPolitico(): bool { return $this->reglaPolitico; }
    public function getTiradorExento(): bool { return $this->tiradorExento; }
    public function getPermiteBots(): bool { return $this->permiteBots; }
    public function getCaras(): array { return $this->caras; }
}
?>
