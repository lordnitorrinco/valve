<?php

/**
 * Centralized security checks applied before any POST request is processed.
 *
 * Each static method either passes silently or terminates the request
 * with an appropriate HTTP error via Response::error().
 */
class Security
{
    /**
     * Reject requests whose Content-Type is not multipart/form-data.
     * Prevents JSON/XML injection and ensures file uploads work correctly.
     */
    public static function checkContentType(): void
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($ct, 'multipart/form-data') === false) {
            SecurityLogger::log('invalid_content_type', ['content_type' => $ct]);
            Response::error('Invalid content type', 415);
        }
    }

    /**
     * Verify that the request comes from the allowed frontend origin.
     * Checks the Origin header first, falls back to Referer.
     *
     * @param string $allowed  The whitelisted origin (e.g. "http://localhost:8080")
     */
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

    /**
     * Detect bots by checking the honeypot field.
     *
     * If the hidden "website" field contains any value, it was filled by a bot.
     * Returns a fake success response so the bot thinks the submission worked.
     */
    public static function checkHoneypot(): void
    {
        if (!empty($_POST['website'] ?? '')) {
            SecurityLogger::log('honeypot_triggered');
            Response::success(['id' => 0, 'message' => 'Solicitud recibida correctamente']);
        }
    }

    /**
     * Generate a stateless CSRF token using HMAC-SHA256.
     *
     * The token encodes the current timestamp signed with the server secret.
     * No session storage is needed — validation re-computes the HMAC.
     *
     * @param  string $secret  Server-side CSRF secret key
     * @return string          Base64-encoded "timestamp.hmac" token
     */
    public static function generateCsrf(string $secret): string
    {
        $time = time();
        $hmac = hash_hmac('sha256', (string) $time, $secret);
        return base64_encode($time . '.' . $hmac);
    }

    /**
     * Validate a CSRF token received in the X-CSRF-Token header.
     *
     * Checks: valid base64, contains separator, not expired, HMAC matches.
     * Terminates with 403 on any failure.
     *
     * @param string $token   The token from the request header
     * @param string $secret  Server-side CSRF secret key
     * @param int    $maxAge  Maximum token age in seconds (default: 15 min)
     */
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

        // Timing-safe comparison to prevent side-channel attacks
        $expected = hash_hmac('sha256', (string) $time, $secret);
        if (!hash_equals($expected, $hmac)) {
            SecurityLogger::log('csrf_mismatch');
            Response::error('Invalid CSRF token', 403);
        }
    }
}
