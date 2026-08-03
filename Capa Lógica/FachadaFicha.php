<?php
require_once 'IFichaLogica.php';
require_once __DIR__ . '/../DTO/FichaDTO.php';

class FachadaFicha
{
    private static $instancia = null;
    private $fichaLogica;

    private function __construct()
    {
        $this->fichaLogica = new FichaLogica();
    }

    public static function getInstancia(): FachadaFicha
    {
        if (self::$instancia === null) {
            self::$instancia = new FachadaFicha();
        }
        return self::$instancia;
    }

    private function __clone()
    {
    }

    public function altaFicha(Ficha $PFicha): bool
    {
        return $this->fichaLogica->altaFicha($PFicha);
    }

    public function bajaFicha(int $PFichaID): bool
    {
        return $this->fichaLogica->bajaFicha($PFichaID);
    }

    public function modificarFicha(Ficha $PFicha): bool
    {
        return $this->fichaLogica->modificarFicha($PFicha);
    }

    public function obtenerFicha(int $PFichaID): ?Ficha
    {
        return $this->fichaLogica->obtenerFicha($PFichaID);
    }

    public function listarFichas(): array
    {
        return $this->fichaLogica->listarFichas();
    }
}
?>