<?php

/**
 * Readiness / liveness probe for orchestrators and monitoring.
 *
 * Returns JSON with database connectivity, PHP version, and free space
 * on the uploads volume. Does not expose secrets or PII.
 * Intended for GET /api/health (no CSRF; GET-only in index.php).
 */
class HealthController
{
    /**
     * Run health checks and respond with JSON.
     *
     * @param PDO    $db          Active PDO connection (already connected)
     * @param string $uploadsDir  Absolute path to CV storage (e.g. /var/www/uploads)
     */
    public static function check(PDO $db, string $uploadsDir): void
    {
        $dbOk = false;
        try {
            $db->query('SELECT 1');
            $dbOk = true;
        } catch (Throwable $e) {
            $dbOk = false;
        }

        $freeBytes = @disk_free_space($uploadsDir);

        $payload = [
            'status'    => $dbOk ? 'healthy' : 'unhealthy',
            'php'       => PHP_VERSION,
            'database'  => $dbOk ? 'ok' : 'error',
            'uploads'   => [
                'path'       => $uploadsDir,
                'bytes_free' => $freeBytes !== false ? $freeBytes : null,
            ],
        ];

        if (!$dbOk) {
            Response::error('Service unavailable', 503, $payload);
        }

        Response::success($payload);
    }
}
