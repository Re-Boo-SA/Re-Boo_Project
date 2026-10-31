<?php

require_once(__DIR__ . '/CabecerasSeguridad.php');
require_once(__DIR__ . '/../../Capa Lógica/FachadaLogica.php');

class SesionSegura
{
    public const MINUTOS_INACTIVIDAD = 30; 
    public const HORAS_DURACION_MAXIMA = 8;  
    private const MINUTOS_RENOVAR_ID = 15; 

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_set_cookie_params([
            'lifetime' => 0,                           
            'path' => '/',
            'secure' => CabecerasSeguridad::esHttps(), 
            'httponly' => true,          
            'samesite' => 'Lax'    
        ]);

        session_start();
        self::controlarVencimiento();
    }

    private static function controlarVencimiento(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            return;
        }

        $ahora = time();
        $inactiva = $ahora - ($_SESSION['ultimo_acceso'] ?? 0) > self::MINUTOS_INACTIVIDAD * 60;
        $vencida = $ahora - ($_SESSION['inicio_sesion'] ?? 0) > self::HORAS_DURACION_MAXIMA * 3600;

        if ($inactiva || $vencida) {
            (new AuditoriaLogica())->registrar('sesion_expirada', 'info', (int) $_SESSION['usuario_id'], null, $inactiva ? 'Inactividad' : 'Duración máxima');
            self::cerrar();
            session_start();
            self::flash('flash_error', 'Tu sesión se cerró por seguridad. Volvé a iniciar sesión.');
            return;
        }

        $_SESSION['ultimo_acceso'] = $ahora;

        if ($ahora - ($_SESSION['id_renovado'] ?? 0) > self::MINUTOS_RENOVAR_ID * 60) {
            session_regenerate_id(true);
            $_SESSION['id_renovado'] = $ahora;
        }
    }

    public static function iniciarSesionUsuario(Usuario $usuario, string $rol): void
    {
        $idioma = $_SESSION['idioma'] ?? 'es';

        session_regenerate_id(true);
        $_SESSION = [];

        $ahora = time();
        $_SESSION['idioma'] = $idioma;
        $_SESSION['usuario_id'] = $usuario->getIdUsuario();
        $_SESSION['nombre_usuario'] = $usuario->getNombreUsuario();
        $_SESSION['correo'] = $usuario->getCorreo();
        $_SESSION['rol'] = $rol;
        $_SESSION['inicio_sesion'] = $ahora;
        $_SESSION['ultimo_acceso'] = $ahora;
        $_SESSION['id_renovado'] = $ahora;
        $_SESSION['csrf_token'] = SeguridadLogica::tokenAleatorio();

        if ($usuario instanceof Jugador) {
            $_SESSION['fichas_actuales'] = $usuario->getFichasActuales();
            $_SESSION['cantidad_fichas'] = $usuario->getCantidadFichas();
            $_SESSION['pnts_partida'] = $usuario->getPntsPartida();
            $_SESSION['partidas_jugadas'] = $usuario->getPartidasJugadas();
            $_SESSION['partidas_ganadas'] = $usuario->getPartidasGanadas();
            $_SESSION['biografia'] = $usuario->getBiografia();
            $_SESSION['estado'] = $usuario->getEstado();
        }
    }

    public static function estaAutenticado(): bool
    {
        return isset($_SESSION['usuario_id'], $_SESSION['rol']) && (int) $_SESSION['usuario_id'] > 0;
    }

    public static function exigirRol(string ...$rolesPermitidos): void
    {
        if (!self::estaAutenticado()) {
            self::redirigirAlLogin('Acceso restringido: iniciá sesión para continuar.');
        }

        $idUsuario = (int) $_SESSION['usuario_id'];
        $rol = (string) $_SESSION['rol'];
        $auditoria = new AuditoriaLogica();

        if (!in_array($rol, $rolesPermitidos, true)) {
            $auditoria->registrar('acceso_denegado', 'aviso', $idUsuario, null, basename($_SERVER['SCRIPT_NAME'] ?? '') . ' (rol ' . $rol . ')');
            mostrarPaginaError(403);
        }

        $fachada = new FachadaLogica();
        if ($fachada->retornoIUsuarioLogica()->cargarUsuario($idUsuario, $rol) === null) {
            $auditoria->registrar('sesion_revocada', 'aviso', $idUsuario, null, 'La cuenta ya no está activa con el rol ' . $rol);
            self::cerrar();
            session_start();
            self::redirigirAlLogin('Tu cuenta ya no tiene acceso. Si creés que es un error, contactá a un administrador.');
        }
    }

    public static function redirigirAlLogin(string $mensaje = ''): never
    {
        if ($mensaje !== '') {
            self::flash('flash_error', $mensaje);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            mostrarPaginaError(401);
        }

        header('Location: login.php', true, 302);
        exit();
    }

    public static function cerrar(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parametros['path'],
            'secure' => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => $parametros['samesite']
        ]);
        session_destroy();
    }


    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = SeguridadLogica::tokenAleatorio();
        }
        return $_SESSION['csrf_token'];
    }

    public static function campoCsrf(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::tokenCsrf(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verificarCsrf(): bool
    {
        $recibido = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return is_string($recibido) && SeguridadLogica::compararSeguro($_SESSION['csrf_token'] ?? '', $recibido);
    }


    public static function flash(string $clave, string $mensaje): void
    {
        $_SESSION[$clave] = $mensaje;
    }

    public static function tomarFlash(string $clave): string
    {
        $mensaje = $_SESSION[$clave] ?? '';
        unset($_SESSION[$clave]);
        return is_string($mensaje) ? $mensaje : '';
    }
}
