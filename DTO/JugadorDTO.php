<?php

require_once(__DIR__ . '/UsuarioDTO.php');

class Jugador extends Usuario
{
    private int $fichasActuales;
    private int $cantidadFichas;
    private int $pntsPartida;
    private int $partidasJugadas;
    private int $partidasGanadas;

    public function __construct(
        int $idUsuario = 0,
        string $correo = '',
        string $contra = '',
        string $nombreUsuario = '',
        int $fichasActuales = 0,
        int $cantidadFichas = 0,
        int $pntsPartida = 0,
        int $partidasJugadas = 0,
        int $partidasGanadas = 0,
        bool $bajaLogica = false
    ) {
        parent::__construct($idUsuario, $correo, $contra, $nombreUsuario, 'jugador', $bajaLogica);
        $this->fichasActuales = $fichasActuales;
        $this->cantidadFichas = $cantidadFichas;
        $this->pntsPartida = $pntsPartida;
        $this->partidasJugadas = $partidasJugadas;
        $this->partidasGanadas = $partidasGanadas;
    }

    public function getFichasActuales(): int
    {
        return $this->fichasActuales;
    }

    public function setFichasActuales(int $fichasActuales): void
    {
        $this->fichasActuales = $fichasActuales;
    }

    public function getCantidadFichas(): int
    {
        return $this->cantidadFichas;
    }

    public function setCantidadFichas(int $cantidadFichas): void
    {
        $this->cantidadFichas = $cantidadFichas;
    }

    public function getPntsPartida(): int
    {
        return $this->pntsPartida;
    }

    public function setPntsPartida(int $pntsPartida): void
    {
        $this->pntsPartida = $pntsPartida;
    }

    public function getPartidasJugadas(): int
    {
        return $this->partidasJugadas;
    }

    public function setPartidasJugadas(int $partidasJugadas): void
    {
        $this->partidasJugadas = $partidasJugadas;
    }

    public function getPartidasGanadas(): int
    {
        return $this->partidasGanadas;
    }

    public function setPartidasGanadas(int $partidasGanadas): void
    {
        $this->partidasGanadas = $partidasGanadas;
    }

    // Métodos de compatibilidad con versiones anteriores
    public function getJugadorID(): int
    {
        return $this->getIdUsuario();
    }

    public function setJugadorID(int $id): void
    {
        $this->setIdUsuario($id);
    }

    public function getPuntosPartida(): int
    {
        return $this->pntsPartida;
    }

    public function setPuntosPartida(int $puntos): void
    {
        $this->pntsPartida = $puntos;
    }
}

?>