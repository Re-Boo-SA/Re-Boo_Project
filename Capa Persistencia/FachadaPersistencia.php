<?php

require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once(__DIR__ . '/../DTO/FichaDTO.php');
require_once(__DIR__ . '/../DTO/JugadasDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/../DTO/PartidasDTO.php');
require_once(__DIR__ . '/../DTO/RecintoDTO.php');
require_once(__DIR__ . '/../DTO/SkinsDTO.php');

require_once('IPersistenciaAdmin.php');
require_once('IPersistenciaFicha.php');
require_once('IPersistenciaJugadas.php');
require_once('IPersistenciaJugador.php');
require_once('IPersistenciaPartidas.php');
require_once('IPersistenciaRecinto.php');
require_once('IPersistenciaSkins.php');
class FachadaPersistencia
{
    public function retornoIPersistenciaAdmin(): IPersistenciaAdmin
    {
        $unIPA = new PersistenciaAdmin();
        return $unIPA;
    }

    public function retornoIPersistenciaFicha(): IPersistenciaFicha
    {
        $unIPF = new PersistenciaFicha();
        return $unIPF;
    }

    public function retornoIPersistenciaJugadas(): IPersistenciaJugadas
    {
        $unIPJ = new PersistenciaJugadas();
        return $unIPJ;
    }

    public function retornoIPersistenciaJugador(): IPersistenciaJugador
    {
        $unIPJ = new PersistenciaJugador();
        return $unIPJ;
    }

    public function retornoIPersistenciaPartidas(): IPersistenciaPartidas
    {
        $unIPP = new PersistenciaPartidas();
        return $unIPP;
    }

    public function retornoIPersistenciaRecinto(): IPersistenciaRecinto
    {
        $unIPR = new PersistenciaRecinto();
        return $unIPR;
    }

    public function retornoIPersistenciaSkins(): IPersistenciaSkins
    {
        $unIPS = new PersistenciaSkins();
        return $unIPS;
    }
}

?>