<?php
session_start();
require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id_partida'])) {
    $idPartida = $_POST['id_partida'] ?? $_GET['id_partida'] ?? 0;
    $password = $_POST['password'] ?? '';

    // Validar en tu capa lógica si requiere pass y redirigir al tablero de juego
    header('Location: inicioHome.php');
    exit();
}