<?php

class JugadasDTO {
private $id_jugada;
private $ronda_actual;
private $turno_actual;

public function setIdJugada($id_jugada) {
    $this->id_jugada = $id_jugada;
}

public function setRondaActual($ronda_actual) {
    $this->ronda_actual = $ronda_actual;
}

public function setTurnoActual($turno_actual) {
    $this->turno_actual = $turno_actual;
}

public function __construct($id_jugada, $ronda_actual, $turno_actual) {
    $this->id_jugada = $id_jugada;
    $this->ronda_actual = $ronda_actual;
    $this->turno_actual = $turno_actual;
}

public function getIdJugada() {
    return $this->id_jugada;
}

public function getRondaActual() {
    return $this->ronda_actual;
}

public function getTurnoActual() {
    return $this->turno_actual;
}

}


?>