<?php
require_once(__DIR__ . '/IDosFactoresLogica.php');
require_once(__DIR__ . '/SeguridadLogica.php');
require_once(__DIR__ . '/AuditoriaLogica.php');
require_once(__DIR__ . '/../Capa Persistencia/FachadaPersistencia.php');

/**
 * Autenticación de dos factores con TOTP (RFC 6238), compatible con Google Authenticator,
 * Microsoft Authenticator o Authy. El código es HMAC-SHA1(secreto, tiempo / 30 s).
 * El secreto se guarda cifrado con AES-256-GCM y los códigos de recuperación hasheados.
 */
class DosFactoresLogica implements IDosFactoresLogica
{
    private const ALFABETO_BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIODO = 30;
    private const DIGITOS = 6;
    private const CANTIDAD_CODIGOS_RECUPERACION = 8;

    private FachadaPersistencia $fachadaPersistencia;

    public function __construct()
    {
        $this->fachadaPersistencia = new FachadaPersistencia();
    }

    // ---------------- Algoritmo TOTP ----------------

    public function generarSecreto(): string
    {
        return self::base32Codificar(random_bytes(20)); // 160 bits, lo que recomienda el RFC
    }

    public static function codigoTOTP(string $secretoBase32, int $momento): string
    {
        $clave = self::base32Decodificar($secretoBase32);
        $contador = pack('J', intdiv($momento, self::PERIODO)); // 8 bytes big-endian
        $hmac = hash_hmac('sha1', $contador, $clave, true);
        $desplazamiento = ord($hmac[19]) & 0x0F;
        $numero = (unpack('N', substr($hmac, $desplazamiento, 4))[1] & 0x7FFFFFFF) % (10 ** self::DIGITOS);
        return str_pad((string) $numero, self::DIGITOS, '0', STR_PAD_LEFT);
    }

    /** Acepta el código actual y el de 30 s antes o después (desfase de reloj del celular). */
    public static function codigoValido(string $secretoBase32, string $codigo, ?int $momento = null): bool
    {
        $codigo = preg_replace('/\s+/', '', $codigo) ?? '';
        if (!preg_match('/^\d{6}$/', $codigo)) {
            return false;
        }
        $momento = $momento ?? time();
        $valido = false;
        foreach ([-1, 0, 1] as $ventana) {
            // hash_equals en todas las ventanas: el tiempo no revela cuál coincidió
            $valido = hash_equals(self::codigoTOTP($secretoBase32, $momento + $ventana * self::PERIODO), $codigo) || $valido;
        }
        return $valido;
    }

    public function uriConfiguracion(string $secreto, string $nombreCuenta): string
    {
        $emisor = 'Draft Der Mauer';
        return 'otpauth://totp/' . rawurlencode($emisor . ':' . $nombreCuenta)
            . '?secret=' . $secreto . '&issuer=' . rawurlencode($emisor)
            . '&algorithm=SHA1&digits=' . self::DIGITOS . '&period=' . self::PERIODO;
    }

    // ---------------- Casos de uso ----------------

    private function leer(int $idUsuario): array
    {
        return $this->fachadaPersistencia->retornoIPersistenciaUsuario()->obtenerDosFactores($idUsuario);
    }

    public function estaActivo(int $idUsuario): bool
    {
        return !empty($this->leer($idUsuario)['secreto']);
    }

    public function confirmarActivacion(int $idUsuario, string $secreto, string $codigo): array
    {
        if (!SeguridadLogica::cifradoConfigurado()) {
            return ['exito' => false, 'mensaje' => 'El servidor no tiene configurada la clave de cifrado (CLAVE_CIFRADO).', 'codigos' => []];
        }

        if (!self::codigoValido($secreto, $codigo)) {
            return ['exito' => false, 'mensaje' => 'El código no es correcto. Revisá que la hora del celular esté bien.', 'codigos' => []];
        }

        [$codigos, $hashes] = $this->nuevosCodigosRecuperacion();

        $ok = $this->fachadaPersistencia->retornoIPersistenciaUsuario()->guardarDosFactores(
            $idUsuario,
            SeguridadLogica::cifrar($secreto),
            json_encode($hashes)
        );

        if (!$ok) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar la configuración.', 'codigos' => []];
        }

        (new AuditoriaLogica())->registrar('2fa_activado', 'critico', $idUsuario);

        return ['exito' => true, 'mensaje' => 'Verificación en dos pasos activada.', 'codigos' => $codigos];
    }

    /** Acepta un código TOTP o uno de los códigos de recuperación (que se consume). */
    public function verificarLogin(int $idUsuario, string $codigo): bool
    {
        $datos = $this->leer($idUsuario);
        if (empty($datos['secreto'])) {
            return false;
        }

        $secreto = SeguridadLogica::descifrar($datos['secreto']);
        if ($secreto !== null && self::codigoValido($secreto, $codigo)) {
            return true;
        }

        return $this->usarCodigoRecuperacion($idUsuario, $codigo, $datos);
    }

    public function desactivar(int $idUsuario, string $password, string $codigo): array
    {
        $usuario = $this->fachadaPersistencia->retornoIPersistenciaUsuario()->buscarPorId($idUsuario);

        if ($usuario === null || !SeguridadLogica::verificarPassword($password, $usuario->getContra())
            || !$this->verificarLogin($idUsuario, $codigo)) {
            (new AuditoriaLogica())->registrar('2fa_desactivar_fallido', 'aviso', $idUsuario);
            return ['exito' => false, 'mensaje' => 'La contraseña o el código no son correctos.'];
        }

        $this->fachadaPersistencia->retornoIPersistenciaUsuario()->guardarDosFactores($idUsuario, null, null);
        (new AuditoriaLogica())->registrar('2fa_desactivado', 'critico', $idUsuario);

        return ['exito' => true, 'mensaje' => 'Verificación en dos pasos desactivada.'];
    }

    public function codigosRestantes(int $idUsuario): int
    {
        $codigos = json_decode((string) ($this->leer($idUsuario)['codigos'] ?? ''), true);
        return is_array($codigos) ? count($codigos) : 0;
    }

    // ---------------- Códigos de recuperación ----------------

    private function nuevosCodigosRecuperacion(): array
    {
        $codigos = [];
        $hashes = [];
        for ($i = 0; $i < self::CANTIDAD_CODIGOS_RECUPERACION; $i++) {
            $codigo = strtoupper(bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)));
            $codigos[] = $codigo;
            $hashes[] = password_hash($codigo, PASSWORD_DEFAULT);
        }
        return [$codigos, $hashes];
    }

    private function usarCodigoRecuperacion(int $idUsuario, string $codigo, array $datos): bool
    {
        $codigo = strtoupper(trim($codigo));
        if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{8}$/', $codigo)) {
            return false;
        }

        $hashes = json_decode((string) ($datos['codigos'] ?? ''), true);
        if (!is_array($hashes)) {
            return false;
        }

        foreach ($hashes as $indice => $hash) {
            if (password_verify($codigo, $hash)) {
                unset($hashes[$indice]); // un solo uso
                $this->fachadaPersistencia->retornoIPersistenciaUsuario()
                    ->guardarDosFactores($idUsuario, $datos['secreto'], json_encode(array_values($hashes)));
                (new AuditoriaLogica())->registrar('2fa_codigo_recuperacion', 'aviso', $idUsuario, null, 'Quedan ' . count($hashes));
                return true;
            }
        }

        return false;
    }

    // ---------------- Base32 (formato estándar de los secretos TOTP) ----------------

    private static function base32Codificar(string $datos): string
    {
        $bits = '';
        foreach (str_split($datos) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $salida = '';
        foreach (str_split($bits, 5) as $grupo) {
            $salida .= self::ALFABETO_BASE32[bindec(str_pad($grupo, 5, '0'))];
        }
        return $salida;
    }

    private static function base32Decodificar(string $texto): string
    {
        $texto = strtoupper(preg_replace('/[\s=]/', '', $texto) ?? '');
        $bits = '';
        foreach (str_split($texto) as $caracter) {
            $posicion = strpos(self::ALFABETO_BASE32, $caracter);
            if ($posicion === false) {
                return '';
            }
            $bits .= str_pad(decbin($posicion), 5, '0', STR_PAD_LEFT);
        }
        $salida = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $salida .= chr(bindec($byte));
            }
        }
        return $salida;
    }
}
?>
