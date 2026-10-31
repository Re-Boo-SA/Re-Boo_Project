<?php

require_once(__DIR__ . '/Seguridad/arranque.php');

header('Content-Type: text/plain; charset=UTF-8');

$fachada = new FachadaLogica();
$disponible = $fachada->retornoIAuditoriaLogica()->sistemaDisponible();

http_response_code($disponible ? 200 : 503);
echo $disponible ? 'OK' : 'ERROR';
