<?php

require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once('/IJugadorLogica.php');
class FachadaJugador
{
    public function retornoIJugadorLogica(): IJugadorLogica{
        $unIJL = new JugadorLogica();
        return $unIJL;
    }
}
?>