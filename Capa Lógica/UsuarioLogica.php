<?php

require_once(__DIR__ . '/IUsuarioLogica.php');
require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');
require_once(__DIR__ . '/SeguridadLogica.php');

class UsuarioLogica implements IUsuarioLogica
{
    private FachadaPersistencia $fachadaPersistencia;

    public function __construct()
    {
        $this->fachadaPersistencia = new FachadaPersistencia();
    }

    public function iniciarSesion(string $identificador, string $password): array
    {
        $identificador = trim($identificador);
        $password = trim($password);

        if (empty($identificador) || empty($password)) {
            return [
                'exito' => false,
                'mensaje' => 'Por favor, ingresa tu correo o tu usuario y tu contraseña.',
                'rol' => '',
                'usuario' => null,
                'redirect' => ''
            ];
        }

        $persistenciaUsuario = $this->fachadaPersistencia->retornoIPersistenciaUsuario();
        $usuario = $persistenciaUsuario->buscarPorIdentificador($identificador);

        if ($usuario === null || $usuario->getBajaLogica()) {
            return [
                'exito' => false,
                'mensaje' => 'Credenciales inválidas o cuenta no registrada.',
                'rol' => '',
                'usuario' => null,
                'redirect' => ''
            ];
        }

        $contraBD = $usuario->getContra();
        $esValida = SeguridadLogica::verificarPassword($password, $contraBD);

        if (!$esValida) {
            return [
                'exito' => false,
                'mensaje' => 'Contraseña incorrecta.',
                'rol' => '',
                'usuario' => null,
                'redirect' => ''
            ];
        }

        $rol = $usuario->getRol();

        if ($rol === 'administrador') {
            $persistenciaAdmin = $this->fachadaPersistencia->retornoIPersistenciaAdmin();
            $admin = $persistenciaAdmin->buscarAdmin($usuario->getIdUsuario());

            if ($admin === null || $admin->getBajaLogica()) {
                return [
                    'exito' => false,
                    'mensaje' => 'El usuario no tiene permisos de administrador activos en el sistema.',
                    'rol' => '',
                    'usuario' => null,
                    'redirect' => ''
                ];
            }

            return [
                'exito' => true,
                'mensaje' => '¡Bienvenido Administrador ' . htmlspecialchars($admin->getNombreUsuario()) . '!',
                'rol' => 'administrador',
                'usuario' => $admin,
                'redirect' => 'panel_admin.php'
            ];

        } elseif ($rol === 'jugador') {
            $persistenciaJugador = $this->fachadaPersistencia->retornoIPersistenciaJugador();
            $jugador = $persistenciaJugador->buscarJugador($usuario->getIdUsuario());

            if ($jugador === null || $jugador->getBajaLogica()) {
                return [
                    'exito' => false,
                    'mensaje' => 'El perfil del jugador se encuentra inactivo o no existe.',
                    'rol' => '',
                    'usuario' => null,
                    'redirect' => ''
                ];
            }

            return [
                'exito' => true,
                'mensaje' => '¡Bienvenido Jugador ' . htmlspecialchars($jugador->getNombreUsuario()) . '!',
                'rol' => 'jugador',
                'usuario' => $jugador,
                'redirect' => 'inicioHome.php'
            ];

        } else {
            return [
                'exito' => false,
                'mensaje' => 'Rol de usuario "' . htmlspecialchars($rol) . '" no reconocido en el sistema.',
                'rol' => '',
                'usuario' => null,
                'redirect' => ''
            ];
        }
    }
}
?>