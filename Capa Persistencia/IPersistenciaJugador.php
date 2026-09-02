<?php

interface IPersistenciaJugador
{
    public function altaJugador(JugadorDTO $jugadorDTO): bool;
    public function buscarJugador(int $JugadorID): ?JugadorDTO;
}
?>