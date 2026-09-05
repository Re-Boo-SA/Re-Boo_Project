<?php

require_once(__DIR__ . '/IJugadorLogica.php');
require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');

class JugadorLogica implements IJugadorLogica
{
    private FachadaPersistencia $fachadaPersistencia;

    public function __construct()
    {
        $this->fachadaPersistencia = new FachadaPersistencia();
    }

    public function registrarJugador(string $nombreUsuario, string $correo, string $password, string $confirmarPassword): array
    {
        $nombreUsuario = trim($nombreUsuario);
        $correo = trim($correo);
        $password = trim($password);
        $confirmarPassword = trim($confirmarPassword);

        if (empty($nombreUsuario) || empty($correo) || empty($password) || empty($confirmarPassword)) {
            return [
                'exito' => false,
                'mensaje' => 'Todos los campos son obligatorios, no deje ninguno vacío.'
            ];
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return [
                'exito' => false,
                'mensaje' => 'El formato del correo electrónico no es válido.'
            ];
        }

        if (strlen($correo) > 100) {
            return [
                'exito' => false,
                'mensaje' => 'El correo electrónico debe tener como máximo 100 caracteres.'
            ];
        }
        $dominio = strtolower(substr(strrchr($correo, '@'), 1));
        $dominiosPermitidos = [
            'gmail.com',
            'googlemail.com',
            'outlook.com',
            'hotmail.com',
            'live.com',
            'msn.com',
            'yahoo.com',
            'ymail.com',
            'rocketmail.com',
            'icloud.com',
            'me.com',
            'mac.com',
            'proton.me',
            'protonmail.com',
            'zoho.com',
            'mail.com',
            'gmx.com',
            'gmx.net',
            'aol.com',
            'fastmail.com',
            'hey.com',
            'tuta.com',
            'tutamail.com',
            'yandex.com',
            'yandex.ru',
            'qq.com',
            '163.com',
            '126.com',
            'sina.com',
            'naver.com',
            'daum.net',
            'hanmail.net',
            'rediffmail.com',
            'web.de',
            'freenet.de',
            't-online.de',
            'orange.fr',
            'laposte.net',
            'free.fr',
            'libero.it',
            'virgilio.it',
            'btinternet.com',
            'sky.com',
            'virginmedia.com',
            'comcast.net',
            'verizon.net',
            'att.net',
            'cox.net'
        ];

        $esDominioArgentino = substr($dominio, -3) === '.ar';

        if (!in_array($dominio, $dominiosPermitidos) && !$esDominioArgentino) {
            return [
                'exito' => false,
                'mensaje' => 'Usar un correo de un proveedor reconocido o un dominio que no sea de Argentina (.ar).'
            ];
        }

        if (strlen($nombreUsuario) < 3 || strlen($nombreUsuario) > 67) {
            return [
                'exito' => false,
                'mensaje' => 'El nombre de usuario debe tener entre 3 y 67 caracteres.'
            ];
        }

        if (strlen($password) < 8) {
            return [
                'exito' => false,
                'mensaje' => 'La contraseña debe tener al menos 8 caracteres.'
            ];
        }

        if ($password !== $confirmarPassword) {
            return [
                'exito' => false,
                'mensaje' => 'Las contraseñas no coinciden.'
            ];
        }

        $persistenciaUsuario = $this->fachadaPersistencia->retornoIPersistenciaUsuario();

        if ($persistenciaUsuario->existeCorreo($correo)) {
            return [
                'exito' => false,
                'mensaje' => 'El correo electrónico ya se encuentra registrado.'
            ];
        }

        if ($persistenciaUsuario->existeNombreUsuario($nombreUsuario)) {
            return [
                'exito' => false,
                'mensaje' => 'El nombre de usuario ya está en uso. Por favor, elige otro.'
            ];
        }

        $contraHash = password_hash($password, PASSWORD_ARGON2ID);

        $usuario = new Usuario($correo, $contraHash, $nombreUsuario, 'jugador', false);

        $jugador = new Jugador($correo, $contraHash, $nombreUsuario, 0, 0, 0, 0, 0, false);


        $persistenciaJugador = $this->fachadaPersistencia->retornoIPersistenciaJugador();
        $ok = $persistenciaJugador->altaJugador($usuario, $jugador);

        if ($ok) {
            return [
                'exito' => true,
                'mensaje' => '¡Cuenta de jugador registrada con éxito! Ya puedes iniciar sesión.'
            ];
        } else {
            return [
                'exito' => false,
                'mensaje' => 'Hubo un inconveniente al guardar el registro en la base de datos.'
            ];
        }
    }

    public function obtenerJugador(int $idUsuario): ?Jugador
    {
        $persistenciaJugador = $this->fachadaPersistencia->retornoIPersistenciaJugador();
        return $persistenciaJugador->buscarJugador($idUsuario);
    }

    public function listarJugadores(): array
    {
        $persistenciaJugador = $this->fachadaPersistencia->retornoIPersistenciaJugador();
        return $persistenciaJugador->listarJugadores();
    }

    public function bajaJugador(int $idUsuario): bool
    {
        if (!empty($idUsuario)) {
            $persistenciaJugador = $this->fachadaPersistencia->retornoIPersistenciaJugador();
            return $persistenciaJugador->bajaLogicaJugador($idUsuario);
        }
        return false;
    }

    public function modificarJugador()
    {
        // Este método queda preparado para la futura modificación de jugadores.
    }
}
?>
