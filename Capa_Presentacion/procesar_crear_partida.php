<?php
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRol('jugador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mostrarPaginaError(405);
}

$idUsuario = (int) $_SESSION['usuario_id'];
$fachada = new FachadaLogica();
$auditoria = $fachada->retornoIAuditoriaLogica();

if ($auditoria->limiteSuperado('partida_creada', 10, 10, $idUsuario)) {
    mostrarPaginaError(429);
}

$nombrePartida = SeguridadLogica::normalizarTexto(is_string($_POST['nombre_partida'] ?? null) ? $_POST['nombre_partida'] : '');
$tipoPartida = $_POST['tipo_partida'] ?? 'publica';
$modo = ($_POST['modo'] ?? 'normal') === 'personalizada' ? 'personalizada' : 'normal';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$numJugadores = filter_var($_POST['num_jugadores'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2, 'max_range' => 5]]);

$valido = $nombrePartida !== ''
    && mb_strlen($nombrePartida) <= 40
    && in_array($tipoPartida, ['publica', 'privada'], true)
    && $numJugadores !== false
    && ($tipoPartida === 'publica' || ($password !== '' && mb_strlen($password) <= 64));

if (!$valido) {
    SesionSegura::flash('flash_perfil', 'Los datos de la partida no son válidos.');
    header('Location: ' . urlAplicacion('inicio'));
    exit();
}

// Reglas de partida personalizada (los rangos los valida ConfigPartida).
$reglas = [];
if ($modo === 'personalizada') {
    foreach (array_keys(ConfigPartida::RANGOS) as $clave) {
        $valor = filter_var($_POST['regla_' . $clave] ?? null, FILTER_VALIDATE_INT);
        if ($valor !== false && $valor !== null) {
            $reglas[$clave] = $valor;
        }
    }
    $reglas['reglaPolitico'] = isset($_POST['regla_reglaPolitico']);
    $reglas['tiradorExento'] = isset($_POST['regla_tiradorExento']);
    $caras = is_array($_POST['regla_caras'] ?? null) ? array_filter($_POST['regla_caras'], 'is_string') : [];
    $reglas['caras'] = array_values(array_intersect(ConfigPartida::TODAS_LAS_CARAS, $caras));
}

$resultado = $fachada->retornoIPartidaLogica()->crearPartida($idUsuario, $nombrePartida, $tipoPartida, $password, $numJugadores, $modo, $reglas);

if (!$resultado['exito']) {
    SesionSegura::flash('flash_perfil', $resultado['mensaje']);
    $destino = isset($resultado['idPartida']) ? urlAplicacion('partida') . '?id=' . (int) $resultado['idPartida'] : urlAplicacion('inicio');
    header('Location: ' . $destino);
    exit();
}

header('Location: ' . urlAplicacion('partida') . '?id=' . (int) $resultado['idPartida']);
exit();
