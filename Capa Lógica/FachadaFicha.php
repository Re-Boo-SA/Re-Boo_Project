<?php

require_once(__DIR__ . '/../DTO/FichaDTO.php');
require_once('/IFichaLogica.php');
class FachadaFicha
{
    public function retornoIFichaLogica(): IFichaLogica{
        $unIFL = new FichaLogica();
        return $unIFL;
    }
}
?>