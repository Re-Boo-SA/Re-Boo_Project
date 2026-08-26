<?php

class Skins {
    private int $SkinID;
    private string $NombreSkin;
    private string $EspecieSkin;
    private float $PrecioSkin;
    private string $DisenioSkin;

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
        $this -> DisenioSkin = $PDisenioSkin;
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
        return $this -> DisenioSkin;
    }
}