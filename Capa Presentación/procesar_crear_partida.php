<?php
session_start();

require_once(__DIR__ . '/../Capa Lógica/FachadaLogica.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombrePartida = trim($_POST['nombre_partida'] ?? '');
    $tipoPartida = $_POST['tipo_partida'] ?? 'publica';
    $password = $_POST['password'] ?? '';
    $numJugadores = (int)($_POST['num_jugadores'] ?? 4);

    if (empty($nombrePartida) || ($tipoPartida === 'privada' && empty($password))) {
        header('Location: inicioHome.php');
        exit();
    }

    header('Location: inicioHome.php');
    exit();
}