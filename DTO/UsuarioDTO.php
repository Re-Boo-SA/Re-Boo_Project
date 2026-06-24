<?php
abstract class Usuario {
    private $Uemail;
    private $Unombre;
    private $Ucontrasenia;
    private $Uid;

    public function setUemail(string $PUemail):void{
        $this -> Uemail = $PUemail;
    }

    public function setUnombre(string $PUnombre):void{
        $this -> Unombre = $PUnombre;
    }

    public function setUcontrasenia(string $PUcontrasenia):void{
        $this -> Ucontrasenia = $PUcontrasenia;
    }

    public function setUid(int $PUid):void{
        $this -> Uid = $PUid;
    }

    public function __construct($PUemail, $PUnombre, $PUcontrasenia, $PUid){
        $this -> setUemail($PUemail);
        $this -> setUnombre($PUnombre);
        $this -> setUcontrasenia($PUcontrasenia);
        $this -> setUid($PUid);
    }

    public function getUemail():string {
    return $this -> Uemail;
    }

    public function getUnombre():string {
        return $this -> Unombre;
    }

    public function getUcontrasenia():string {
        return $this -> Ucontrasenia;
    }
    
    public function getUid():int {
        return $this -> Uid;
    }
}