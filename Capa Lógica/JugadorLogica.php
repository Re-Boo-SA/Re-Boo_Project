<?php

require_once(__DIR__ . '/IJugadorLogica.php');
require_once(__DIR__ . '/../DTO/UsuarioDTO.php');
require_once(__DIR__ . '/../DTO/JugadorDTO.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');
require_once(__DIR__ . '/SeguridadLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');

class JugadorLogica implements IJugadorLogica
{
    private FachadaPersistencia $fachadaPersistencia;

    public function __construct()
    {
        $this->fachadaPersistencia = new FachadaPersistencia();
    }

    public function registrarJugador(string $nombreUsuario, string $correo, string $password, string $confirmarPassword): array
    {
        $nombreUsuario = SeguridadLogica::normalizarTexto($nombreUsuario);
        $correo = mb_strtolower(trim($correo), 'UTF-8');

        if ($nombreUsuario === '' || $correo === '' || $password === '' || $confirmarPassword === '') {
            return [
                'exito' => false,
                'mensaje' => 'Todos los campos son obligatorios, no deje ninguno vacío.'
            ];
        }

        $auditoria = new AuditoriaLogica();
        if ($auditoria->limiteSuperado('registro', 5, 60)) {
            $auditoria->registrar('registro_limitado', 'aviso', null, $correo);
            return [
                'exito' => false,
                'mensaje' => 'Se crearon demasiadas cuentas desde tu conexión. Intentá más tarde.'
            ];
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return [
                'exito' => false,
                'mensaje' => 'El formato del correo electrónico no es válido.'
            ];
        }

        if (mb_strlen($correo) > 100) {
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

        $errorNombre = SeguridadLogica::validarNombreUsuario($nombreUsuario);
        if ($errorNombre !== null) {
            return [
                'exito' => false,
                'mensaje' => $errorNombre
            ];
        }

        $errorPassword = SeguridadLogica::validarPassword($password, $nombreUsuario, $correo);
        if ($errorPassword !== null) {
            return [
                'exito' => false,
                'mensaje' => $errorPassword
            ];
        }

        if (!hash_equals($password, $confirmarPassword)) {
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

        $contraHash = SeguridadLogica::hashPassword($password);

        $usuario = new Usuario(0, $correo, $contraHash, $nombreUsuario, 'jugador', false);

        $jugador = new Jugador(0, $correo, $contraHash, $nombreUsuario, 0, 0, 0, 0, 0, false);


        $persistenciaJugador = $this->fachadaPersistencia->retornoIPersistenciaJugador();
        $ok = $persistenciaJugador->altaJugador($usuario, $jugador);

        if ($ok) {
            $auditoria->registrar('registro', 'info', $usuario->getIdUsuario(), $nombreUsuario);
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

    public function gestionarBajaJugador(int $idUsuario): string
    {
        if ($idUsuario <= 0) {
            return 'error';
        }

        $persistenciaJugador = $this->fachadaPersistencia->retornoIPersistenciaJugador();
        return $persistenciaJugador->gestionarBajaJugador($idUsuario);
    }

    public function modificarJugador(Usuario $usuario, Jugador $jugador): bool
    {
    $persistenciaJugador = $this->fachadaPersistencia->retornoIPersistenciaJugador();
        return $persistenciaJugador->modificarJugador($usuario, $jugador);
    }

    public function actualizarPerfil(int $idUsuario, string $nombreUsuario, string $biografia, string $estado): array
    {
        $auditoria = new AuditoriaLogica();

        if ($auditoria->limiteSuperado('perfil_modificado', 20, 10, $idUsuario)) {
            return ['exito' => false, 'mensaje' => 'Hiciste demasiados cambios seguidos. Esperá unos minutos.'];
        }

        $nombreUsuario = SeguridadLogica::normalizarTexto($nombreUsuario);
        $biografia = SeguridadLogica::normalizarTexto(str_replace(["\r\n", "\r"], "\n", $biografia), true);

        $errorNombre = SeguridadLogica::validarNombreUsuario($nombreUsuario);
        if ($errorNombre !== null) {
            return ['exito' => false, 'mensaje' => $errorNombre];
        }

        if (mb_strlen($biografia, 'UTF-8') > 300) {
            return ['exito' => false, 'mensaje' => 'La biografía puede tener como máximo 300 caracteres.'];
        }

        // Lista blanca: solo se aceptan los estados que existen
        if (!in_array($estado, ['en_linea', 'ocupado', 'no_molestar', 'invisible'], true)) {
            return ['exito' => false, 'mensaje' => 'Estado no válido.'];
        }

        $persistenciaUsuario = $this->fachadaPersistencia->retornoIPersistenciaUsuario();
        if ($persistenciaUsuario->existeNombreUsuario($nombreUsuario, $idUsuario)) {
            return ['exito' => false, 'mensaje' => 'El nombre de usuario ya está en uso. Por favor, elige otro.'];
        }

        // El jugador siempre es el de la sesión: no se acepta un id que venga del formulario (IDOR)
        $jugador = $this->obtenerJugador($idUsuario);
        if ($jugador === null) {
            return ['exito' => false, 'mensaje' => 'No se encontró el jugador.'];
        }

        $nombreAnterior = $jugador->getNombreUsuario();
        $jugador->setNombreUsuario($nombreUsuario);
        $jugador->setBiografia($biografia);
        $jugador->setEstado($estado);

        if (!$this->modificarJugador($jugador, $jugador)) {
            return ['exito' => false, 'mensaje' => 'No se pudieron guardar los cambios.'];
        }

        $detalle = $nombreAnterior !== $nombreUsuario ? 'Nombre: ' . $nombreAnterior . ' -> ' . $nombreUsuario : null;
        $auditoria->registrar('perfil_modificado', 'info', $idUsuario, null, $detalle);

        return ['exito' => true, 'mensaje' => 'Perfil actualizado.', 'jugador' => $jugador];
    }

    public function guardarFotoPerfil(int $idUsuario, array $archivo, string $carpetaDestino): array
    {
        $auditoria = new AuditoriaLogica();
        $maximoBytes = 2 * 1024 * 1024;

        if ($auditoria->limiteSuperado('foto_subida', 10, 60, $idUsuario)) {
            return ['exito' => false, 'mensaje' => 'Subiste demasiadas fotos. Intentá más tarde.'];
        }

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
            return ['exito' => false, 'mensaje' => 'No se recibió la imagen.'];
        }

        if (($archivo['size'] ?? 0) <= 0 || $archivo['size'] > $maximoBytes) {
            return ['exito' => false, 'mensaje' => 'La imagen puede pesar como máximo 2 MB.'];
        }

        $tiposPermitidos = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        $tipoReal = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        $dimensiones = @getimagesize($archivo['tmp_name']);

        if (!in_array($tipoReal, $tiposPermitidos, true) || $dimensiones === false
            || $dimensiones[0] < 1 || $dimensiones[1] < 1 || $dimensiones[0] > 4096 || $dimensiones[1] > 4096) {
            $auditoria->registrar('foto_rechazada', 'aviso', $idUsuario, null, 'Tipo detectado: ' . $tipoReal);
            return ['exito' => false, 'mensaje' => 'El archivo no es una imagen PNG, JPG, GIF o WEBP válida.'];
        }

        if (!is_dir($carpetaDestino) && !mkdir($carpetaDestino, 0755, true)) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar la imagen.'];
        }

        $destino = rtrim($carpetaDestino, '/') . '/perfil_' . $idUsuario . '.png';

        if (function_exists('imagecreatefromstring')) {
            $original = @imagecreatefromstring((string) file_get_contents($archivo['tmp_name']));
            if ($original === false) {
                return ['exito' => false, 'mensaje' => 'El archivo no es una imagen válida.'];
            }

            $lado = 256;
            $ancho = imagesx($original);
            $alto = imagesy($original);
            $escala = min($lado / $ancho, $lado / $alto, 1);
            $nueva = imagecreatetruecolor(max(1, (int) round($ancho * $escala)), max(1, (int) round($alto * $escala)));
            imagealphablending($nueva, false);
            imagesavealpha($nueva, true);
            imagecopyresampled($nueva, $original, 0, 0, 0, 0, imagesx($nueva), imagesy($nueva), $ancho, $alto);

            $ok = imagepng($nueva, $destino);
            imagedestroy($original);
            imagedestroy($nueva);
        } else {
            $ok = move_uploaded_file($archivo['tmp_name'], $destino);
        }

        if (!$ok) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar la imagen.'];
        }

        @chmod($destino, 0644);
        $auditoria->registrar('foto_subida', 'info', $idUsuario);

        return ['exito' => true, 'mensaje' => 'Foto actualizada.'];
    }
}
?>
