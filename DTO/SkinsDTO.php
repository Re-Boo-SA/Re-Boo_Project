<?php

class Skins {
    private $SkinID;
    private $NombreSkin;
    private $EspecieSkin;
    private $PrecioSkin;
    private $disenioSkin; // Agregado para almacenar el diseño de la skin

    public function setSkinID(int $PSkinID):void{
        $this -> SkinID = $PSkinID;
    }

    public function setNombreSkin(string $PNombreSkin):void{
        $this -> NombreSkin = $PNombreSkin;
    }

    public function setEspecieSkin(string $PEspecieSkin):void{
        $this -> EspecieSkin = $PEspecieSkin;
    }

    public function setPrecioSkin(float $PPrecioSkin):void{
        $this -> PrecioSkin = $PPrecioSkin;
    }

    public function setDisenioSkin(string $PDisenioSkin):void{
        $this -> disenioSkin = $PDisenioSkin;
    }

    public function __construct($PSkinID, $PNombreSkin, $PEspecieSkin, $PPrecioSkin, $PDisenioSkin){
        $this -> setSkinID($PSkinID);
        $this -> setNombreSkin($PNombreSkin);
        $this -> setEspecieSkin($PEspecieSkin);
        $this -> setPrecioSkin($PPrecioSkin);
        $this -> setDisenioSkin($PDisenioSkin);
    }

    public function getSkinID():int {
        return $this -> SkinID;
    }

    public function getNombreSkin():string {
        return $this -> NombreSkin;
    }

    public function getEspecieSkin():string {
        return $this -> EspecieSkin;
    }

    public function getPrecioSkin():float {
        return $this -> PrecioSkin;
    }

    public function getDisenioSkin():string {
        return $this -> disenioSkin;
    }
}