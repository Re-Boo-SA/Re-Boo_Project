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
require_once('IPersistenciaAuditoria.php');
require_once('IPersistenciaRanking.php');
require_once('PersistenciaRanking.php');
require_once('IPersistenciaCola.php');
require_once('PersistenciaCola.php');
require_once('IPersistenciaMundo.php');
require_once('PersistenciaMundo.php');
require_once('IPersistenciaMisiones.php');
require_once('PersistenciaMisiones.php');
require_once('IPersistenciaSocial.php');
require_once('PersistenciaSocial.php');
require_once('IPersistenciaTorneos.php');
require_once('PersistenciaTorneos.php');
require_once('IPersistenciaNpc.php');
require_once('PersistenciaNpc.php');
require_once('IPersistenciaSucesos.php');
require_once('PersistenciaSucesos.php');
require_once('IPersistenciaCampana.php');
require_once('PersistenciaCampana.php');
require_once('PersistenciaAuditoria.php');
require_once('IPersistenciaReporte.php');
require_once('PersistenciaReporte.php');

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

    public function retornoIPersistenciaAuditoria(): IPersistenciaAuditoria
    {
        return PersistenciaAuditoria::getInstancia();
    }

    public function retornoIPersistenciaRanking(): IPersistenciaRanking
    {
        return PersistenciaRanking::getInstancia();
    }

    public function retornoIPersistenciaCola(): IPersistenciaCola
    {
        return PersistenciaCola::getInstancia();
    }

    public function retornoIPersistenciaCampana(): IPersistenciaCampana
    {
        return PersistenciaCampana::getInstancia();
    }

    public function retornoIPersistenciaSucesos(): IPersistenciaSucesos
    {
        return PersistenciaSucesos::getInstancia();
    }

    public function retornoIPersistenciaNpc(): IPersistenciaNpc
    {
        return PersistenciaNpc::getInstancia();
    }

    public function retornoIPersistenciaTorneos(): IPersistenciaTorneos
    {
        return PersistenciaTorneos::getInstancia();
    }

    public function retornoIPersistenciaSocial(): IPersistenciaSocial
    {
        return PersistenciaSocial::getInstancia();
    }

    public function retornoIPersistenciaMisiones(): IPersistenciaMisiones
    {
        return PersistenciaMisiones::getInstancia();
    }

    public function retornoIPersistenciaMundo(): IPersistenciaMundo
    {
        return PersistenciaMundo::getInstancia();
    }

    public function retornoIPersistenciaReporte(): IPersistenciaReporte
    {
        return PersistenciaReporte::getInstancia();
    }
}
?>