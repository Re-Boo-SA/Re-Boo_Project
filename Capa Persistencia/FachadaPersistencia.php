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
        return PersistenciaAdmin::getInstancia();
    }

    public function retornoIPersistenciaFicha(): IPersistenciaFicha
    {
        return PersistenciaFicha::getInstancia();
    }

    public function retornoIPersistenciaJugadas(): IPersistenciaJugadas
    {
        return PersistenciaJugadas::getInstancia();
    }

    public function retornoIPersistenciaJugador(): IPersistenciaJugador
    {
        return PersistenciaJugador::getInstancia();
    }

    public function retornoIPersistenciaPartidas(): IPersistenciaPartidas
    {
        return PersistenciaPartidas::getInstancia();
    }

    public function retornoIPersistenciaRecinto(): IPersistenciaRecinto
    {
        return PersistenciaRecinto::getInstancia();
    }

    public function retornoIPersistenciaSkins(): IPersistenciaSkins
    {
        return PersistenciaSkins::getInstancia();
    }
}

?>