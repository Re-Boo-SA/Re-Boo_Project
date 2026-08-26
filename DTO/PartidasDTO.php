<?php
class Partidas {
    private int $PartidaID;
    private int $puntaje_maximo;
    private string $fecha_hora;

    public function setPartidaID(int $PartidaID): void {
        $this->PartidaID = $PartidaID;
    }

    public function setPuntajeMaximo(int $puntaje_maximo): void {
        $this->puntaje_maximo = $puntaje_maximo;
    }

    public function setFechaHora(string $fecha_hora): void {
        $this->fecha_hora = $fecha_hora;
    }

    public function __construct($PartidaID, $puntaje_maximo, $fecha_hora) {
        $this->PartidaID = $PartidaID;
        $this->puntaje_maximo = $puntaje_maximo;
        $this->fecha_hora = $fecha_hora;
    }

    public function getPartidaID(): int {
        return $this->PartidaID;
    }


    public function getPuntajeMaximo(): int {
        return $this->puntaje_maximo;
    }

    public function getFechaHora(): string {
        return $this->fecha_hora;
    }

}
?>