<?php

require_once(__DIR__ . '/IUsuarioLogica.php');
require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/../DTO/AdministradorDTO.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');
require_once(__DIR__ . '/SeguridadLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');

class UsuarioLogica implements IUsuarioLogica
{
    public const MAX_FALLOS_CUENTA = 5;
    public const MAX_FALLOS_IP = 20;
    public const MINUTOS_BLOQUEO = 67;

    private const MENSAJE_CREDENCIALES = 'Credenciales inválidas.';

    private FachadaPersistencia $fachadaPersistencia;
    private AuditoriaLogica $auditoria;

    public function __construct()
    {
        $this->fachadaPersistencia = new FachadaPersistencia();
        $this->auditoria = new AuditoriaLogica();
    }

    private function error(string $mensaje): array
    {
        return [
            'exito' => false,
            'mensaje' => $mensaje,
            'rol' => '',
            'usuario' => null,
            'redirect' => ''
        ];
    }

    public function iniciarSesion(string $identificador, string $password): array
    {
        $identificador = SeguridadLogica::normalizarTexto($identificador);

        if ($identificador === '' || $password === '') {
            return $this->error('Por favor, ingresa tu correo o tu usuario y tu contraseña.');
        }

        if (mb_strlen($identificador) > 100 || strlen($password) > 1024) {
            return $this->error(self::MENSAJE_CREDENCIALES);
        }

        $identificadorLog = mb_strtolower($identificador, 'UTF-8');

        if ($this->estaBloqueado($identificadorLog, null)) {
            $this->auditoria->registrar('login_bloqueado', 'aviso', null, $identificadorLog);
            return $this->error('Demasiados intentos fallidos. Esperá ' . self::MINUTOS_BLOQUEO . ' minutos e intentá de nuevo.');
        }

        $persistenciaUsuario = $this->fachadaPersistencia->retornoIPersistenciaUsuario();
        $usuario = $persistenciaUsuario->buscarPorIdentificador($identificador);

        if ($usuario === null || $usuario->getBajaLogica()) {
            SeguridadLogica::verificarPasswordFicticia($password);
            $this->auditoria->registrar('login_fallido', 'aviso', null, $identificadorLog, 'Cuenta inexistente o dada de baja');
            return $this->error(self::MENSAJE_CREDENCIALES);
        }

        if ($this->estaBloqueado(null, $usuario->getIdUsuario())) {
            $this->auditoria->registrar('login_bloqueado', 'aviso', $usuario->getIdUsuario(), $identificadorLog);
            return $this->error('Demasiados intentos fallidos. Esperá ' . self::MINUTOS_BLOQUEO . ' minutos e intentá de nuevo.');
        }

        $contraBD = $usuario->getContra();
        $esValida = SeguridadLogica::verificarPassword($password, $contraBD);

        if (!$esValida) {
            $this->auditoria->registrar('login_fallido', 'aviso', $usuario->getIdUsuario(), $identificadorLog, 'Contraseña incorrecta');

            if ($this->estaBloqueado(null, $usuario->getIdUsuario())) {
                $this->auditoria->registrar('cuenta_bloqueada', 'critico', $usuario->getIdUsuario(), $identificadorLog,
                    self::MAX_FALLOS_CUENTA . ' intentos fallidos');
            }

            return $this->error(self::MENSAJE_CREDENCIALES);
        }

        if (SeguridadLogica::necesitaRehash($contraBD)) {
            $persistenciaUsuario->actualizarContra($usuario->getIdUsuario(), SeguridadLogica::hashPassword($password));
        }

        $rol = $usuario->getRol();
        $perfil = $this->cargarUsuario($usuario->getIdUsuario(), $rol);

        if ($perfil === null) {
            $this->auditoria->registrar('login_fallido', 'aviso', $usuario->getIdUsuario(), $identificadorLog, 'Perfil inactivo o rol inválido');
            return $this->error($rol === 'administrador'
                ? 'El usuario no tiene permisos de administrador activos en el sistema.'
                : 'El perfil del jugador se encuentra inactivo o no existe.');
        }

        $this->auditoria->registrar('login_exitoso', 'info', $usuario->getIdUsuario(), $identificadorLog);

        return [
            'exito' => true,
            'mensaje' => $rol === 'administrador'
                ? '¡Bienvenido Administrador ' . $perfil->getNombreUsuario() . '!'
                : '¡Bienvenido Jugador ' . $perfil->getNombreUsuario() . '!',
            'rol' => $rol,
            'usuario' => $perfil,
            'redirect' => $rol === 'administrador' ? 'panel_admin.php' : 'inicioHome.php'
        ];
    }

    private function estaBloqueado(?string $identificador, ?int $idUsuario): bool
    {
        if ($idUsuario !== null) {
            return $this->auditoria->contar('login_fallido', self::MINUTOS_BLOQUEO, false, null, $idUsuario) >= self::MAX_FALLOS_CUENTA;
        }

        return $this->auditoria->contar('login_fallido', self::MINUTOS_BLOQUEO, false, $identificador) >= self::MAX_FALLOS_CUENTA
            || $this->auditoria->contar('login_fallido', self::MINUTOS_BLOQUEO, true) >= self::MAX_FALLOS_IP;
    }

    public function cargarUsuario(int $idUsuario, string $rol): ?Usuario
    {
        if ($idUsuario <= 0) {
            return null;
        }

        if ($rol === 'administrador') {
            $admin = $this->fachadaPersistencia->retornoIPersistenciaAdmin()->buscarAdmin($idUsuario);
            return ($admin !== null && !$admin->getBajaLogica()) ? $admin : null;
        }

        if ($rol === 'jugador') {
            $jugador = $this->fachadaPersistencia->retornoIPersistenciaJugador()->buscarJugador($idUsuario);
            return ($jugador !== null && !$jugador->getBajaLogica()) ? $jugador : null;
        }

        return null;
    }
}
?>
