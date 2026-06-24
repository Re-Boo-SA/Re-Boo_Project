<?php
class Partidas {
    private $id_partida;
    private $posicion_podio;
    private $puntaje_maximo;
    private $fecha_hora;

    public function setIdPartida($id_partida) {
        $this->id_partida = $id_partida;
    }

    public function setPosicionPodio($posicion_podio) {
        $this->posicion_podio = $posicion_podio;
    }

    public function setPuntajeMaximo($puntaje_maximo) {
        $this->puntaje_maximo = $puntaje_maximo;
    }

    public function setFechaHora($fecha_hora) {
        $this->fecha_hora = $fecha_hora;
    }

    public function __construct($id_partida, $posicion_podio, $puntaje_maximo, $fecha_hora) {
        $this->id_partida = $id_partida;
        $this->posicion_podio = $posicion_podio;
        $this->puntaje_maximo = $puntaje_maximo;
        $this->fecha_hora = $fecha_hora;
    }

    public function getIdPartida() {
        return $this->id_partida;
    }

    public function getPosicionPodio() {
        return $this->posicion_podio;
    }

    public function getPuntajeMaximo() {
        return $this->puntaje_maximo;
    }

    public function getFechaHora() {
        return $this->fecha_hora;
    }

}
?>