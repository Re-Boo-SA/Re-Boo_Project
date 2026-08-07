<?php

require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once(__DIR__ . '/../DTO/FichaDTO.php');
require_once(__DIR__ . '/../DTO/JugadasDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/../DTO/PartidasDTO.php');
require_once(__DIR__ . '/../DTO/RecintoDTO.php');
require_once(__DIR__ . '/../DTO/SkinsDTO.php');

require_once('IAdminLogica.php');
require_once('IFichaLogica.php');
require_once('IJugadorLogica.php');
require_once('IPartidasLogica.php');
require_once('IRecintoLogica.php');

class FachadaLogica
{
    public function retornoIAdminLogica(): IAdminLogica
    {
        $unIAL = new AdminLogica();
        return $unIAL;
    }

    public function retornoIFichaLogica(): IFichaLogica
    {
        $unIFL = new FichaLogica();
        return $unIFL;
    }

    public function retornoIJugadorLogica(): IJugadorLogica
    {
        $unIJL = new JugadorLogica();
        return $unIJL;
    }

    public function retornoIPartidaLogica(): IPartidaLogica
    {
        $unIPL = new PartidaLogica();
        return $unIPL;
    }

    public function retornoIRecintoLogica(): IRecintoLogica
    {
        $unIRL = new RecintoLogica();
        return $unIRL;
    }
}

?>