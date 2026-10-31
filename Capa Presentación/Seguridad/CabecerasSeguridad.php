<?php

class CabecerasSeguridad
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }
        return self::$nonce;
    }

    public static function esHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    public static function enviar(bool $usaRecaptcha = false): void
    {
        if (headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');

        $nonce = self::nonce();

        $politica = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'" . ($usaRecaptcha ? ' https://www.recaptcha.net https://www.gstatic.com' : ''),
            "style-src 'self'" . ($usaRecaptcha ? " 'unsafe-inline'" : ''),
            "font-src 'self'",
            "img-src 'self' data:",
            "connect-src 'self'" . ($usaRecaptcha ? ' https://www.recaptcha.net' : ''),
            "frame-src " . ($usaRecaptcha ? 'https://www.recaptcha.net https://www.google.com' : "'none'"),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'"
        ];
        header('Content-Security-Policy: ' . implode('; ', $politica));

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

        header('Cache-Control: no-store, max-age=0');
        header('Pragma: no-cache');

        if (self::esHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
