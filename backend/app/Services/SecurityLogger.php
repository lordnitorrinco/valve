<?php

class SecurityLogger
{
    public static function log(string $event, array $context = []): void
    {
        $entry = json_encode(array_merge([
            'event' => $event,
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua'    => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
            'time'  => date('c'),
        ], $context), JSON_UNESCAPED_UNICODE);

        error_log("[SECURITY] $entry");
    }
}
