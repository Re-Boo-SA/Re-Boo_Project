<?php
class Jugador {
    private int $JugadorID;
    private string $NombreUsuario;
    private string $Email;
    private string $Password;
    private array $Cantidadfichas;
    private int $PuntosPartida;
    private int $PartidasJugadas;
    private int $PartidasGanadas;
    private bool $Activo;

    public function setNombreUsuario(string $PNombreUsuario):void{
        $this -> NombreUsuario = $PNombreUsuario;
    }

    public function setEmail(string $PEmail):void{
        $this -> Email = $PEmail;
    }

    public function setPassword(string $PPassword):void{
        $this -> Password = $PPassword;
    }

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


    public function setActivo(bool $PActivo):void{
        $this -> Activo = $PActivo;
    }

    public function __construct(int $PJugadorID, array $PCantidadfichas, int $PPuntosPartida, int $PPartidasJugadas, int $PPartidasGanadas, string $PNombreUsuario = '', string $PEmail = '', string $PPassword = '', bool $PActivo = true) {
        $this -> setJugadorID($PJugadorID);
        $this -> setCantidadfichas($PCantidadfichas);
        $this -> setPuntosPartida($PPuntosPartida);
        $this -> setPartidasJugadas($PPartidasJugadas);
        $this -> setPartidasGanadas($PPartidasGanadas);
        $this -> setNombreUsuario($PNombreUsuario);
        $this -> setEmail($PEmail);
        $this -> setPassword($PPassword);
        $this -> setActivo($PActivo);
    }

    public function getJugadorID():int {
        return $this -> JugadorID;
    }

    public function getNombreUsuario():string {
        return $this -> NombreUsuario;
    }

    public function getEmail():string {
        return $this -> Email;
    }

    public function getPassword():string {
        return $this -> Password;
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

    public function getActivo():bool {
        return $this -> Activo;
    }

}

?>