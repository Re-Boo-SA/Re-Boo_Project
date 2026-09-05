<?php

require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once(__DIR__ . '/../DTO/FichaDTO.php');
require_once(__DIR__ . '/../DTO/JugadasDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/../DTO/PartidasDTO.php');
require_once(__DIR__ . '/../DTO/RecintoDTO.php');
require_once(__DIR__ . '/../DTO/SkinsDTO.php');

require_once('IPersistenciaUsuario.php');
require_once('PersistenciaUsuario.php');
require_once('IPersistenciaAdmin.php');
require_once('PersistenciaAdmin.php');
require_once('IPersistenciaFicha.php');
require_once('PersistenciaFicha.php');
require_once('IPersistenciaJugadas.php');
require_once('PersistenciaJugadas.php');
require_once('IPersistenciaJugador.php');
require_once('PersistenciaJugador.php');
require_once('IPersistenciaPartidas.php');
require_once('PersistenciaPartidas.php');
require_once('IPersistenciaRecinto.php');
require_once('PersistenciaRecinto.php');
require_once('IPersistenciaSkins.php');
require_once('PersistenciaSkins.php');

class FachadaPersistencia
{
    public function retornoIPersistenciaUsuario(): IPersistenciaUsuario
    {
        return PersistenciaUsuario::getInstancia();
    }

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