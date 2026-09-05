<?php

class Usuario
{
    private int $idUsuario;
    private string $correo;
    private string $contra;
    private string $nombreUsuario;
    private string $rol;
    private bool $bajaLogica;

    public function __construct(
        int $idUsuario = 0,
        string $correo = '',
        string $contra = '',
        string $nombreUsuario = '',
        string $rol = 'jugador',
        bool $bajaLogica = false
    ) {
        $this->idUsuario = $idUsuario;
        $this->correo = $correo;
        $this->contra = $contra;
        $this->nombreUsuario = $nombreUsuario;
        $this->rol = $rol;
        $this->bajaLogica = $bajaLogica;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function setIdUsuario(int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
    }

    public function getCorreo(): string
    {
        return $this->correo;
    }

    public function setCorreo(string $correo): void
    {
        $this->correo = $correo;
    }

    public function getContra(): string
    {
        return $this->contra;
    }

    public function setContra(string $contra): void
    {
        $this->contra = $contra;
    }

    public function getNombreUsuario(): string
    {
        return $this->nombreUsuario;
    }

    public function setNombreUsuario(string $nombreUsuario): void
    {
        $this->nombreUsuario = $nombreUsuario;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function setRol(string $rol): void
    {
        $this->rol = $rol;
    }

    public function getBajaLogica(): bool
    {
        return $this->bajaLogica;
    }

    public function setBajaLogica(bool $bajaLogica): void
    {
        $this->bajaLogica = $bajaLogica;
    }
}

?>