<?php

require_once(__DIR__ . '/UsuarioDTO.php');

class Administrador extends Usuario
{
    public function __construct(
        int $idUsuario = 0,
        string $correo = '',
        string $contra = '',
        string $nombreUsuario = '',
        bool $bajaLogica = false
    ) {
        parent::__construct($idUsuario, $correo, $contra, $nombreUsuario, 'administrador', $bajaLogica);
    }

    public function setId(int $id): void
    {
        $this->setIdUsuario($id);
    }

    public function getId(): int
    {
        return $this->getIdUsuario();
    }
}

?>