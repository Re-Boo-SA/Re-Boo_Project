<?php
/*
 * API del mundo abierto.
 *   GET  api-mundo                         -> estado completo (jugadores, chat, dominio, temporada); pedirlo ~cada 10 s
 *   POST api-mundo accion=mover x y dir desde -> responde liviano: posición validada, jugadores y mensajes nuevos (desde = último id de mensaje visto)
 *   GET  api-mundo?accion=mapa             -> el mapa Tiled + edificios + distritos (una vez al entrar)
 *   GET  api-mundo?accion=salas&distrito=N -> mesas esperando en ese café
 *   POST api-mundo accion=mover x y dir    -> mueve (validado) y devuelve el estado
 *   POST api-mundo accion=bando bando      -> elegir 'oeste' o 'este'
 *   GET  api-mundo?accion=diario&lado=oeste|este -> diario del día (plantillas para traducir)
 *   POST api-mundo accion=visitar_suceso   -> dar una vuelta por el mercado (XP una vez por suceso)
 *   POST api-mundo accion=gesto gesto(1 saludar, 2 aplaudir, 3 encogerse, 4 sentarse)
 *   POST api-mundo accion=apariencia piel pelo gorro -> cambiar el aspecto del personaje
 *   GET  api-mundo?accion=misiones          -> XP, nivel, contactos, misiones activas y logros (avanza lo que el servidor puede comprobar)
 *   POST api-mundo accion=hablar contacto   -> hablar con un contacto (hay que estar cerca)
 *   POST api-mundo accion=aceptar mision    -> aceptar una misión ofrecida
 *   POST api-mundo accion=desafiar mision   -> empezar (o retomar) la partida de un desafío
 *   POST api-mundo accion=charlar contacto nodo opcion -> charla con opciones (nodo vacío = empezar)
 *   POST api-mundo accion=decir texto      -> burbuja de chat
 *   POST api-mundo accion=reportar id      -> reportar un mensaje al admin
 *   POST api-mundo accion=crear_mesa distrito jugadores
 *   POST api-mundo accion=unirse id password
 */
define('RESPUESTA_JSON', true);
require_once(__DIR__ . '/Seguridad/arranque.php');
require_once(__DIR__ . '/../Capa_Logica/FachadaLogica.php');

SesionSegura::exigirRolRevalidando('jugador', 30);
header('Content-Type: application/json; charset=UTF-8');

$idUsuario = (int) $_SESSION['usuario_id'];
$fachada = new FachadaLogica();
$mundo = $fachada->retornoIMundoLogica();

function responder(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

function entero(string $clave, int $defecto = 0): int
{
    $valor = filter_var($_POST[$clave] ?? $_GET[$clave] ?? null, FILTER_VALIDATE_INT);
    return $valor === false || $valor === null ? $defecto : $valor;
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = is_string($_POST['accion'] ?? $_GET['accion'] ?? null) ? ($_POST['accion'] ?? $_GET['accion']) : '';

if ($metodo === 'GET') {
    if ($accion === 'mapa') {
        session_write_close();
        responder(['exito' => true] + $mundo->mapa());
    }
    if ($accion === 'misiones') {
        responder(['exito' => true] + $fachada->retornoIMisionLogica()->estado($idUsuario));
    }
    if ($accion === 'diario') {
        require_once(__DIR__ . '/../Capa_Logica/DiarioLogica.php');
        $lado = is_string($_GET['lado'] ?? null) ? $_GET['lado'] : '';
        responder((new DiarioLogica())->edicion($lado !== '' ? $lado : ($mundo->estado($idUsuario)['yo']['bando'] ?? 'oeste')));
    }
    if ($accion === 'salas') {
        // Mesas esperando (para sentarse) y en curso (para mirar, Fase 4)
        responder(['exito' => true, 'salas' => $mundo->salas(entero('distrito')),
            'enCurso' => $fachada->retornoISocialLogica()->partidasParaMirar(entero('distrito'))]);
    }
    session_write_close();
    responder(['exito' => true, 'social' => $fachada->retornoISocialLogica()->resumen($idUsuario),
        'torneo' => $fachada->retornoITorneoLogica()->miPartidaPendiente($idUsuario),
        'sucesos' => ['actual' => SucesoLogica::actual(), 'proximo' => SucesoLogica::proximo()]] + $mundo->estado($idUsuario));
}

switch ($accion) {
    case 'mover':
        if (!SesionSegura::dentroDelLimite('mundo_mover', 400, 60)) {
            mostrarPaginaError(429);
        }
        $posicion = $mundo->mover($idUsuario, entero('x'), entero('y'), entero('dir'));
        session_write_close(); // libera la sesión: los pedidos siguientes del mismo jugador no esperan
        responder(['exito' => true, 'posicion' => $posicion] + $mundo->estadoLiviano($idUsuario, entero('desde')));
    case 'bando':
        responder($mundo->elegirBando($idUsuario, is_string($_POST['bando'] ?? null) ? $_POST['bando'] : ''));
    case 'visitar_suceso':
        responder((new SucesoLogica())->visitar($idUsuario));
    case 'gesto':
        responder($mundo->gesto($idUsuario, entero('gesto')));
    case 'personaje':
        responder($mundo->guardarPersonaje($idUsuario, is_string($_POST['codigo'] ?? null) ? $_POST['codigo'] : ''));
    case 'apariencia':
        responder($mundo->guardarApariencia($idUsuario, entero('piel'), entero('pelo'), entero('gorro')));
    case 'hablar':
        if (!SesionSegura::dentroDelLimite('mundo_hablar', 40, 60)) {
            mostrarPaginaError(429);
        }
        $r = $fachada->retornoIMisionLogica()->hablar($idUsuario, is_string($_POST['contacto'] ?? null) ? $_POST['contacto'] : '');
        responder($r, $r['exito'] ? 200 : 422);
    case 'charlar':
        if (!SesionSegura::dentroDelLimite('mundo_hablar', 60, 60)) {
            mostrarPaginaError(429);
        }
        $r = $fachada->retornoIMisionLogica()->charlar($idUsuario, is_string($_POST['contacto'] ?? null) ? $_POST['contacto'] : '',
            is_string($_POST['nodo'] ?? null) ? $_POST['nodo'] : '', entero('opcion'));
        responder($r, $r['exito'] ? 200 : 422);
    case 'desafiar':
        $r = $fachada->retornoIMisionLogica()->desafiar($idUsuario, is_string($_POST['mision'] ?? null) ? $_POST['mision'] : '');
        responder($r, $r['exito'] ? 200 : 422);
    case 'aceptar':
        $r = $fachada->retornoIMisionLogica()->aceptar($idUsuario, is_string($_POST['mision'] ?? null) ? $_POST['mision'] : '');
        responder($r, $r['exito'] ? 200 : 422);
    case 'decir':
        if (!SesionSegura::dentroDelLimite('mundo_chat', 6, 20)) {
            responder(['exito' => false, 'mensaje' => 'Pará un poco: muchos mensajes seguidos.'], 429);
        }
        responder($mundo->decir($idUsuario, is_string($_POST['texto'] ?? null) ? $_POST['texto'] : ''));
    case 'reportar':
        if (!SesionSegura::dentroDelLimite('mundo_reporte', 5, 300)) {
            mostrarPaginaError(429);
        }
        responder($mundo->reportar($idUsuario, entero('id')));
    case 'crear_mesa':
        $r = $mundo->crearMesa($idUsuario, entero('distrito'), entero('jugadores', 4), (string) ($_SESSION['nombre_usuario'] ?? 'Jugador'));
        responder($r, $r['exito'] ? 200 : 422);
    case 'unirse':
        $r = $fachada->retornoIPartidaLogica()->unirse($idUsuario, entero('id'), is_string($_POST['password'] ?? null) ? $_POST['password'] : '');
        responder($r, $r['exito'] ? 200 : 422);
    default:
        responder(['exito' => false, 'mensaje' => 'Acción desconocida.'], 400);
}
