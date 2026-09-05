<?php

require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once(__DIR__ . '/../DTO/FichaDTO.php');
require_once(__DIR__ . '/../DTO/JugadasDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/../DTO/PartidasDTO.php');
require_once(__DIR__ . '/../DTO/RecintoDTO.php');
require_once(__DIR__ . '/../DTO/SkinsDTO.php');

require_once(__DIR__ . '/IUsuarioLogica.php');
require_once(__DIR__ . '/UsuarioLogica.php');
require_once(__DIR__ . '/IAdminLogica.php');
require_once(__DIR__ . '/AdminLogica.php');
require_once(__DIR__ . '/IJugadorLogica.php');
require_once(__DIR__ . '/JugadorLogica.php');
require_once(__DIR__ . '/IFichaLogica.php');
require_once(__DIR__ . '/FichaLogica.php');
require_once(__DIR__ . '/IPartidaLogica.php');
require_once(__DIR__ . '/PartidaLogica.php');
require_once(__DIR__ . '/IRecintoLogica.php');
require_once(__DIR__ . '/RecintoLogica.php');

class FachadaLogica
{
    public function retornoIUsuarioLogica(): IUsuarioLogica
    {
        return new UsuarioLogica();
    }

    public function retornoIAdminLogica(): IAdminLogica
    {
        return new AdminLogica();
    }

    public function retornoIJugadorLogica(): IJugadorLogica
    {
        return new JugadorLogica();
    }

    public function retornoIFichaLogica(): IFichaLogica
    {
        return new FichaLogica();
    }

    public function retornoIPartidaLogica(): IPartidaLogica
    {
        return new PartidaLogica();
    }

    public function retornoIRecintoLogica(): IRecintoLogica
    {
        return new RecintoLogica();
    }
}
?>