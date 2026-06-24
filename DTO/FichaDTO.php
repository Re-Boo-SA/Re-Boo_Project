<?php
class Ficha {
    private $FichaID;
    private $FichaEspecie;


    public function setFichaID(int $PFichaID):void{
        $this -> FichaID = $PFichaID;
    }

    public function setFichaEspecie(string $PFichaEspecie):void{
        $this -> FichaEspecie = $PFichaEspecie;
    }


    public function __construct($PFichaID, $PFichaEspecie){
        $this -> setFichaID($PFichaID);
        $this -> setFichaEspecie($PFichaEspecie);
    }

    public function getFichaID():int {
        return $this -> FichaID;
    }

    public function getFichaEspecie():string {
        return $this -> FichaEspecie;
    }
}

?>