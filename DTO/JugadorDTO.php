<?php
class Jugador {
    private $JugadorID;
    private $Cantidadfichas;
    private $PuntosPartida;
    private $PartidasJugadas;
    private $PartidasGanadas;

    public function setJugadorID(int $PJugadorID):void{
        $this -> JugadorID = $PJugadorID;
    }

    public function setCantidadfichas(array $PCantidadfichas):void{
        $this -> Cantidadfichas = $PCantidadfichas;
    }

    public function setPuntosPartida(int $PPuntosPartida):void{
        $this -> PuntosPartida = $PPuntosPartida;
    }

    public function setPartidasJugadas(int $PPartidasJugadas):void{
        $this -> PartidasJugadas = $PPartidasJugadas;
    }

    public function setPartidasGanadas(int $PPartidasGanadas):void{
        $this -> PartidasGanadas = $PPartidasGanadas;
    }

    public function __construct($PJugadorID, $PCantidadfichas, $PPuntosPartida, $PPartidasJugadas, $PPartidasGanadas) {
        $this -> setJugadorID($PJugadorID);
        $this -> setCantidadfichas($PCantidadfichas);
        $this -> setPuntosPartida($PPuntosPartida);
        $this -> setPartidasJugadas($PPartidasJugadas);
        $this -> setPartidasGanadas($PPartidasGanadas);
    }

    public function getJugadorID():int {
        return $this -> JugadorID;
    }

    public function getCantidadfichas():array {
        return $this -> Cantidadfichas;
    }

    public function getPuntosPartida():int {
        return $this -> PuntosPartida;
    }

    public function getPartidasJugadas():int {
        return $this -> PartidasJugadas;
    }

    public function getPartidasGanadas():int {
        return $this -> PartidasGanadas;
    }

}

?>