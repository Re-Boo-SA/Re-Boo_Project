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
require_once(__DIR__ . '/ISkinsLogica.php');
require_once(__DIR__ . '/SkinsLogica.php');
require_once(__DIR__ . '/IAuditoriaLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/IRankingLogica.php');
require_once(__DIR__ . '/RankingLogica.php');
require_once(__DIR__ . '/IColaLogica.php');
require_once(__DIR__ . '/ColaLogica.php');
require_once(__DIR__ . '/IMundoLogica.php');
require_once(__DIR__ . '/MundoLogica.php');
require_once(__DIR__ . '/IMisionLogica.php');
require_once(__DIR__ . '/MisionLogica.php');
require_once(__DIR__ . '/ISocialLogica.php');
require_once(__DIR__ . '/SocialLogica.php');
require_once(__DIR__ . '/ITorneoLogica.php');
require_once(__DIR__ . '/TorneoLogica.php');
require_once(__DIR__ . '/CampanaLogica.php');
require_once(__DIR__ . '/IReporteLogica.php');
require_once(__DIR__ . '/ReporteLogica.php');

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

    public function retornoISkinsLogica(): ISkinsLogica
    {
        return new SkinsLogica();
    }

    public function retornoIAuditoriaLogica(): IAuditoriaLogica
    {
        return new AuditoriaLogica();
    }

    public function retornoIRankingLogica(): IRankingLogica
    {
        return new RankingLogica();
    }

    public function retornoIColaLogica(): IColaLogica
    {
        return new ColaLogica();
    }

    public function retornoICampanaLogica(): CampanaLogica
    {
        return new CampanaLogica();
    }

    public function retornoITorneoLogica(): ITorneoLogica
    {
        return new TorneoLogica();
    }

    public function retornoISocialLogica(): ISocialLogica
    {
        return new SocialLogica();
    }

    public function retornoIMisionLogica(): IMisionLogica
    {
        return new MisionLogica();
    }

    public function retornoIMundoLogica(): IMundoLogica
    {
        return new MundoLogica();
    }

    public function retornoIReporteLogica(): IReporteLogica
    {
        return new ReporteLogica();
    }
}
?>