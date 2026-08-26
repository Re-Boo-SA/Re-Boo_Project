<?php
class Administrador {
    //ABML para control de administradores
    
    private int $AdminId;
    private string $NombreUsuario;
    private string $Email;
    private string $Password;
    private bool $Activo;

    public function __construct(int $AdminId, string $PNombreUsuario = '', string $PEmail = '', string $PPassword = '', bool $PActivo = true) {
        $this->setAdminId($AdminId);
        $this->setNombreUsuario($PNombreUsuario);
        $this->setEmail($PEmail);
        $this->setPassword($PPassword);
        $this->setActivo($PActivo);
    }

    public function setAdminId(int $AdminId): void {
        $this->AdminId = $AdminId;
    }

    public function setNombreUsuario(string $PNombreUsuario): void {
        $this->NombreUsuario = $PNombreUsuario;
    }

    public function setEmail(string $PEmail): void {
        $this->Email = $PEmail;
    }

    public function setPassword(string $PPassword): void {
        $this->Password = $PPassword;
    }

    public function setActivo(bool $PActivo): void {
        $this->Activo = $PActivo;
    }


    public function getAdminId(): int {
        return $this->AdminId;
    }

    public function getNombreUsuario(): string {
        return $this->NombreUsuario;
    }

    public function getEmail(): string {
        return $this->Email;
    }

    public function getPassword(): string {
        return $this->Password;
    }

    public function getActivo(): bool {
        return $this->Activo;
    }

}
?>