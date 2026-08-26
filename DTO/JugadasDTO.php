<?php

class JugadasDTO {
private int $JugadaID;
private int $ronda_actual;
private int $turno_actual;

public function setJugadaID(int $JugadaID): void {
    $this->JugadaID = $JugadaID;
}

public function setRondaActual(int $ronda_actual): void {
    $this->ronda_actual = $ronda_actual;
}

public function setTurnoActual(int $turno_actual): void {
    $this->turno_actual = $turno_actual;
}

public function __construct($JugadaID, $ronda_actual, $turno_actual) {
    $this->JugadaID = $JugadaID;
    $this->ronda_actual = $ronda_actual;
    $this->turno_actual = $turno_actual;
}

public function getJugadaID(): int {
    return $this->JugadaID;
}

public function getRondaActual(): int {
    return $this->ronda_actual;
}

public function getTurnoActual(): int {
    return $this->turno_actual;
}

}
?>