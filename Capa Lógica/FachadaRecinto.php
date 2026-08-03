<?php

require_once(__DIR__ . '/../DTO/RecintoDTO.php');
require_once('/IRecintoLogica.php');
class FachadaRecinto
{
    public function retornoIRecintoLogica(): IRecintoLogica{
        $unIRL = new RecintoLogica();
        return $unIRL;
    }
}
?>