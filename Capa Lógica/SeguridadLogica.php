<?php
require_once(__DIR__ . '/../config.php');

class SeguridadLogica
{
    // Política de contraseñas
    public const LARGO_MINIMO_CONTRA = 10;
    public const LARGO_MAXIMO_CONTRA = 128;

    private const OPCIONES_ARGON2 = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1];

    public static function modoDesarrollo(): bool
    {
        return defined('APP_MODO') && APP_MODO === 'desarrollo';
    }

    private static function valorSecreto(string $variableEntorno, string $constante): string
    {
        $valor = getenv($variableEntorno);
        if ($valor === false || $valor === '') {
            $valor = defined($constante) ? (string) constant($constante) : '';
        }
        return $valor;
    }

    public static function obtenerPepper(): string
    {
        $pepper = self::valorSecreto('REBOO_PASSWORD_PEPPER', 'PEPPER_CONTRASENA');

        if ($pepper === '') {
            throw new RuntimeException('REBOO_PASSWORD_PEPPER no está configurada en el servidor.');
        }

        return $pepper;
    }

    public static function obtenerClaveRecaptcha(): string
    {
        $clave = self::valorSecreto('REBOO_RECAPTCHA_SECRET_KEY', 'RECAPTCHA_CLAVE_SECRETA');

        if ($clave === '') {
            throw new RuntimeException('REBOO_RECAPTCHA_SECRET_KEY no está configurada en el servidor.');
        }

        return $clave;
    }

    public static function obtenerClavePublicaRecaptcha(): string
    {
        return self::valorSecreto('REBOO_RECAPTCHA_SITE_KEY', 'RECAPTCHA_CLAVE_SITIO');
    }

   
    public static function hashPassword(string $password): string
    {
        $hash = password_hash($password . self::obtenerPepper(), PASSWORD_ARGON2ID, self::OPCIONES_ARGON2);

        if ($hash === false) {
            throw new RuntimeException('No se pudo generar el hash de la contraseña.');
        }

        return $hash;
    }

    public static function verificarPassword(string $password, string $hash): bool
    {
        return password_verify($password . self::obtenerPepper(), $hash);
    }

    public static function necesitaRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, self::OPCIONES_ARGON2);
    }

    public static function verificarPasswordFicticia(string $password): void
    {
        static $hashFicticio = null;
        if ($hashFicticio === null) {
            $hashFicticio = password_hash('contraseña-ficticia', PASSWORD_ARGON2ID, self::OPCIONES_ARGON2);
        }
        password_verify($password . self::obtenerPepper(), $hashFicticio);
    }
    public static function validarPassword(string $password, string $nombreUsuario = '', string $correo = ''): ?string
    {
        $largo = mb_strlen($password, 'UTF-8');

        if ($largo < self::LARGO_MINIMO_CONTRA) {
            return 'La contraseña debe tener al menos ' . self::LARGO_MINIMO_CONTRA . ' caracteres.';
        }

        if ($largo > self::LARGO_MAXIMO_CONTRA) {
            return 'La contraseña puede tener como máximo ' . self::LARGO_MAXIMO_CONTRA . ' caracteres.';
        }

        $minuscula = mb_strtolower($password, 'UTF-8');

        if (self::esContrasenaComun($minuscula)) {
            return 'Esa contraseña está entre las más usadas y se adivina enseguida. Elegí otra.';
        }

        if (count(array_unique(mb_str_split($minuscula, 1, 'UTF-8'))) < 5) {
            return 'La contraseña repite demasiado los mismos caracteres.';
        }

        $nombre = mb_strtolower(trim($nombreUsuario), 'UTF-8');
        $localCorreo = mb_strtolower(strstr($correo, '@', true) ?: '', 'UTF-8');

        if (($nombre !== '' && mb_strlen($nombre) >= 3 && str_contains($minuscula, $nombre))
            || ($localCorreo !== '' && mb_strlen($localCorreo) >= 3 && str_contains($minuscula, $localCorreo))) {
            return 'La contraseña no puede contener tu nombre de usuario ni tu correo.';
        }

        return null;
    }

    public static function esContrasenaComun(string $passwordMinuscula): bool
    {
        static $lista = null;
        if ($lista === null) {
            $lineas = @file(__DIR__ . '/datos/contrasenas_comunes.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lista = $lineas === false ? [] : array_flip($lineas);
        }
        return isset($lista[$passwordMinuscula]);
    }

    public static function normalizarTexto(string $texto, bool $permitirSaltosDeLinea = false): string
    {
        $texto = trim($texto);
        if (class_exists('Normalizer')) {
            $normalizado = Normalizer::normalize($texto, Normalizer::FORM_C);
            if ($normalizado !== false) {
                $texto = $normalizado;
            }
        }
        $patron = $permitirSaltosDeLinea ? '/[^\P{Cc}\n]|\p{Cf}/u' : '/[\p{Cc}\p{Cf}]/u';
        return preg_replace($patron, '', $texto) ?? '';
    }

    public static function validarNombreUsuario(string $nombreUsuario): ?string
    {
        $largo = mb_strlen($nombreUsuario, 'UTF-8');

        if ($largo < 3 || $largo > 30) {
            return 'El nombre de usuario debe tener entre 3 y 30 caracteres.';
        }

        if (!preg_match('/^[\p{Latin}0-9_.\-]+$/u', $nombreUsuario)) {
            return 'El nombre de usuario solo puede tener letras, números, guion, guion bajo y punto.';
        }

        return null;
    }

    public static function tokenAleatorio(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function compararSeguro(string $esperado, string $recibido): bool
    {
        return $esperado !== '' && hash_equals($esperado, $recibido);
    }

    public static function ipCliente(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'cli';
    }

    private static function claveCifrado(): string
    {
        $hex = self::valorSecreto('REBOO_CLAVE_CIFRADO', 'CLAVE_CIFRADO');

        if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) {
            throw new RuntimeException('CLAVE_CIFRADO debe tener 64 caracteres hexadecimales (256 bits).');
        }

        return hex2bin($hex);
    }

    public static function cifradoConfigurado(): bool
    {
        try {
            self::claveCifrado();
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    public static function cifrar(string $textoPlano): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cifrado = openssl_encrypt($textoPlano, 'aes-256-gcm', self::claveCifrado(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($cifrado === false) {
            throw new RuntimeException('No se pudo cifrar el dato.');
        }

        return 'v1:' . base64_encode($iv . $tag . $cifrado);
    }

    public static function descifrar(string $datoCifrado): ?string
    {
        if (!str_starts_with($datoCifrado, 'v1:')) {
            return null;
        }

        $crudo = base64_decode(substr($datoCifrado, 3), true);
        if ($crudo === false || strlen($crudo) < 29) {
            return null;
        }

        $plano = openssl_decrypt(substr($crudo, 28), 'aes-256-gcm', self::claveCifrado(), OPENSSL_RAW_DATA, substr($crudo, 0, 12), substr($crudo, 12, 16));

        return $plano === false ? null : $plano;
    }


    public static function verificarRecaptcha(string $token, string $accionEsperada): bool
    {
        if (self::modoDesarrollo() && self::valorSecreto('REBOO_RECAPTCHA_SECRET_KEY', 'RECAPTCHA_CLAVE_SECRETA') === '') {
            return true;
        }

        if ($token === '' || strlen($token) > 4096) {
            return false;
        }

        $datos = http_build_query([
            'secret' => self::obtenerClaveRecaptcha(),
            'response' => $token,
            'remoteip' => self::ipCliente()
        ]);

        $opciones = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                    'Content-Length: ' . strlen($datos) . "\r\n",
                'content' => $datos,
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ];

        $respuesta = @file_get_contents(
            'https://www.recaptcha.net/recaptcha/api/siteverify',
            false,
            stream_context_create($opciones)
        );

        if ($respuesta === false) {
            return false;
        }

        $resultado = json_decode($respuesta, true);

        if (!is_array($resultado)) {
            return false;
        }

        $valido = ($resultado['success'] ?? false) === true
            && ($resultado['action'] ?? '') === $accionEsperada
            && (float) ($resultado['score'] ?? 0) >= 0.5;

        return $valido;
    }
}
