<?php

class SeguridadLogica
{
    public static function obtenerPepper(): string
    {
        $pepper = getenv('REBOO_PASSWORD_PEPPER');

        if ($pepper === false || $pepper === '') {
            throw new RuntimeException('REBOO_PASSWORD_PEPPER no está configurada en el servidor.');
        }

        return $pepper;
    }

    public static function obtenerClaveRecaptcha(): string
    {
        $clave = getenv('REBOO_RECAPTCHA_SECRET_KEY');

        if ($clave === false || $clave === '') {
            throw new RuntimeException('REBOO_RECAPTCHA_SECRET_KEY no está configurada en el servidor.');
        }

        return $clave;
    }

    public static function obtenerClavePublicaRecaptcha(): string
    {
        $clave = getenv('REBOO_RECAPTCHA_SITE_KEY');

        if ($clave === false || $clave === '') {
            throw new RuntimeException('REBOO_RECAPTCHA_SITE_KEY no está configurada en el servidor.');
        }

        return $clave;
    }

    public static function hashPassword(string $password): string
    {
        $hash = password_hash($password . self::obtenerPepper(), PASSWORD_ARGON2ID);

        if ($hash === false) {
            throw new RuntimeException('No se pudo generar el hash de la contraseña.');
        }

        return $hash;
    }

    public static function verificarPassword(string $password, string $hash): bool
    {
        return password_verify($password . self::obtenerPepper(), $hash);
    }

    public static function verificarRecaptcha(string $token, string $accionEsperada): bool
    {
        if ($token === '') {
            return false;
        }

        $datos = http_build_query([
            'secret' => self::obtenerClaveRecaptcha(),
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
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
            'https://www.google.com/recaptcha/api/siteverify',
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
