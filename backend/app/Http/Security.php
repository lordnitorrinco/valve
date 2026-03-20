<?php

class Security
{
    public static function checkContentType(): void
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($ct, 'multipart/form-data') === false) {
            SecurityLogger::log('invalid_content_type', ['content_type' => $ct]);
            Response::error('Invalid content type', 415);
        }
    }

    public static function checkOrigin(string $allowed): void
    {
        $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($origin && $origin !== $allowed) {
            SecurityLogger::log('blocked_origin', ['origin' => $origin]);
            Response::error('Forbidden', 403);
        }

        if (!$origin && $referer && !str_starts_with($referer, $allowed)) {
            SecurityLogger::log('blocked_referer', ['referer' => $referer]);
            Response::error('Forbidden', 403);
        }
    }

    public static function checkHoneypot(): void
    {
        if (!empty($_POST['website'] ?? '')) {
            SecurityLogger::log('honeypot_triggered');
            Response::success(['id' => 0, 'message' => 'Solicitud recibida correctamente']);
        }
    }

    public static function generateCsrf(string $secret): string
    {
        $time = time();
        $hmac = hash_hmac('sha256', (string) $time, $secret);
        return base64_encode($time . '.' . $hmac);
    }

    public static function validateCsrf(string $token, string $secret, int $maxAge = 900): void
    {
        $decoded = base64_decode($token, true);
        if (!$decoded || !str_contains($decoded, '.')) {
            SecurityLogger::log('csrf_invalid', ['token' => substr($token, 0, 20)]);
            Response::error('Invalid CSRF token', 403);
        }

        [$time, $hmac] = explode('.', $decoded, 2);
        $time = (int) $time;

        if (time() - $time > $maxAge) {
            SecurityLogger::log('csrf_expired');
            Response::error('CSRF token expired', 403);
        }

        $expected = hash_hmac('sha256', (string) $time, $secret);
        if (!hash_equals($expected, $hmac)) {
            SecurityLogger::log('csrf_mismatch');
            Response::error('Invalid CSRF token', 403);
        }
    }
}
