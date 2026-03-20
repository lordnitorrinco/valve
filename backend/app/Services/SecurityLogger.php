<?php

/**
 * Structured security event logger.
 *
 * Outputs JSON-formatted log entries to PHP's error_log, which Docker
 * captures as container stdout/stderr. Each entry includes the event name,
 * client IP, user agent, timestamp, and any extra context.
 */
class SecurityLogger
{
    /**
     * Log a security-relevant event.
     *
     * @param string $event    Event identifier (e.g. "csrf_invalid", "rate_limited")
     * @param array  $context  Additional key-value pairs to include in the log
     */
    public static function log(string $event, array $context = []): void
    {
        $entry = json_encode(array_merge([
            'event' => $event,
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua'    => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200), // Truncate to prevent log injection
            'time'  => date('c'),
        ], $context), JSON_UNESCAPED_UNICODE);

        error_log("[SECURITY] $entry");
    }
}
