<?php
require_once(__DIR__ . '/ConfigPartidaDTO.php');

/*
 * Foto completa de una partida tal como está en la base, en un momento.
 * La arma la persistencia (PersistenciaPartidas::cargarEstado) y la usa
 * la lógica (PartidaLogica) para decidir qué pasa. Lleva TODO, incluso
 * las manos de todos: por eso nunca se manda tal cual al navegador; la
 * lógica arma una "vista" recortada para cada jugador.
 */
class EstadoPartida
{
    /** Fila de PARTIDAS + 'cara' (código) + 'segundosFase' (calculado por MySQL). */
    public array $partida;
    public ConfigPartida $config;
    /** Participantes ordenados por asiento: IDUsuario, Nombre, Asiento, Estado, Confirmado, EsBot, SegundosSinLatido... */
    public array $participantes;
    /** Fichas físicas: nro, tipo, ubicacion, idUsuario, recinto, posicion, ronda, turno. */
    public array $fichas;
    /** Historial: IDJugada, nro, idUsuario, recinto, origen, tipo, ronda, turno. */
    public array $jugadas;

    public function __construct(array $partida, ConfigPartida $config, array $participantes, array $fichas, array $jugadas)
    {
        $this->partida = $partida;
        $this->config = $config;
        $this->participantes = $participantes;
        $this->fichas = $fichas;
        $this->jugadas = $jugadas;
    }

    public function id(): int { return (int) $this->partida['IDPartida']; }
    public function ronda(): int { return (int) $this->partida['RondaActual']; }
    public function turno(): int { return (int) $this->partida['TurnoActual']; }
    public function estado(): string { return $this->partida['Estado']; }
    public function fase(): string { return $this->partida['Fase']; }
    public function cara(): ?string { return $this->partida['cara']; }
    public function modo(): string { return $this->partida['Modo']; }
    public function segundosFase(): int { return (int) $this->partida['segundosFase']; }

    /** Participantes que siguen jugando (no abandonaron), por asiento. */
    public function activos(): array
    {
        return array_values(array_filter($this->participantes, fn($p) => $p['Estado'] === 'activo'));
    }

    public function participante(int $idUsuario): ?array
    {
        foreach ($this->participantes as $p) {
            if ((int) $p['IDUsuario'] === $idUsuario) {
                return $p;
            }
        }
        return null;
    }

    public function participanteEnAsiento(int $asiento): ?array
    {
        foreach ($this->participantes as $p) {
            if ((int) $p['Asiento'] === $asiento) {
                return $p;
            }
        }
        return null;
    }

    /** Tablero de un jugador en el formato del motor. */
    public function tableroDe(int $idUsuario): array
    {
        $tablero = [];
        foreach ($this->fichas as $f) {
            if ($f['ubicacion'] === 'tablero' && $f['idUsuario'] === $idUsuario) {
                $tablero[] = [
                    'nro' => $f['nro'], 'tipo' => $f['tipo'], 'recinto' => $f['recinto'],
                    'posicion' => $f['posicion'], 'ronda' => $f['ronda'], 'turno' => $f['turno'],
                ];
            }
        }
        return $tablero;
    }

    /** Mano de un jugador: lista de ['nro', 'tipo']. */
    public function manoDe(int $idUsuario): array
    {
        $mano = [];
        foreach ($this->fichas as $f) {
            if ($f['ubicacion'] === 'mano' && $f['idUsuario'] === $idUsuario) {
                $mano[] = ['nro' => $f['nro'], 'tipo' => $f['tipo']];
            }
        }
        return $mano;
    }

    public function ficha(int $nro): ?array
    {
        foreach ($this->fichas as $f) {
            if ($f['nro'] === $nro) {
                return $f;
            }
        }
        return null;
    }

    /** Ficha que el jugador colocó en el turno actual (o null). */
    public function colocadaEsteTurno(int $idUsuario): ?array
    {
        foreach ($this->fichas as $f) {
            if ($f['ubicacion'] === 'tablero' && $f['idUsuario'] === $idUsuario
                && $f['ronda'] === $this->ronda() && $f['turno'] === $this->turno()) {
                return $f;
            }
        }
        return null;
    }

    /** ¿Ya hizo el movimiento obligatorio de la cara "cambiar ficha" este turno? */
    public function movioEsteTurno(int $idUsuario): bool
    {
        foreach ($this->jugadas as $j) {
            if ($j['tipo'] === 'mover' && $j['idUsuario'] === $idUsuario
                && $j['ronda'] === $this->ronda() && $j['turno'] === $this->turno()) {
                return true;
            }
        }
        return false;
    }

    /** Fichas movidas este turno (los demás no las ven hasta que termine). */
    public function nrosMovidosEsteTurno(): array
    {
        $nros = [];
        foreach ($this->jugadas as $j) {
            if ($j['tipo'] === 'mover' && $j['ronda'] === $this->ronda() && $j['turno'] === $this->turno()) {
                $nros[] = $j['nro'];
            }
        }
        return $nros;
    }

    public function hayBots(): bool
    {
        foreach ($this->participantes as $p) {
            if ((int) $p['EsBot'] === 1) {
                return true;
            }
        }
        return false;
    }
}
?>
