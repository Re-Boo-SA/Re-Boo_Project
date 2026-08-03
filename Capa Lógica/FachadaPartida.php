<?php

require_once(__DIR__ . '/../DTO/PartidaDTO.php');
require_once('/IPartidaLogica.php');
class FachadaPartida
{
    public function retornoIPartidaLogica(): IPartidaLogica{
        $unIPL = new PartidaLogica();
        return $unIPL;
    }
}
?>