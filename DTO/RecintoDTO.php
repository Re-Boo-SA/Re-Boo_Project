<?php
class Recinto {
    private $RecintoID;
    private $NombreRecinto;

    public function setRecintoID(int $PRecintoID):void{
        $this -> RecintoID = $PRecintoID;
    }

    public function setNombreRecinto(string $PNombreRecinto):void{
        $this -> NombreRecinto = $PNombreRecinto;
    }


    public function __construct($PRecintoID, $PNombreRecinto){
        $this -> setRecintoID($PRecintoID);
        $this -> setNombreRecinto($PNombreRecinto);
    }

    public function getRecintoID():int {
        return $this -> RecintoID;
    }

    public function getNombreRecinto():string {
        return $this -> NombreRecinto;
    }

}
?>