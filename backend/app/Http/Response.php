<?php

/**
 * HTTP response helper.
 *
 * All terminal methods throw HaltException instead of calling exit(),
 * making every code path unit-testable. The front controller catches
 * HaltException and sends the actual HTTP response.
 */
class Response
{
    /**
     * Set CORS headers to allow requests from the frontend origin.
     *
     * @param string $allowedOrigin  The permitted origin domain
     */
    public static function cors(string $allowedOrigin = '*'): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
        header('Access-Control-Expose-Headers: X-Request-ID, X-Response-Time');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Append X-Response-Time header (wall time since request start).
     * Called before halting responses so clients and proxies can measure latency.
     */
    public static function timingHeader(): void
    {
        if (!isset($GLOBALS['request_start'])) {
            return;
        }
        $seconds = microtime(true) - $GLOBALS['request_start'];
        header('X-Response-Time: ' . sprintf('%.3f', $seconds));
    }

    /**
     * Build a JSON response and halt execution via HaltException.
     *
     * @param array $data    Response payload
     * @param int   $status  HTTP status code
     */
    public static function json(array $data, int $status = 200): never
    {
        self::timingHeader();
        http_response_code($status);
        throw new HaltException($status, json_encode($data));
    }

    /** Send a 200 OK JSON response. */
    public static function success(array $data): never
    {
        self::json($data, 200);
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message  Human-readable error description
     * @param int    $status   HTTP error code (default: 400)
     * @param array  $extra    Additional fields merged into the response
     */
    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(array_merge(['error' => $message], $extra), $status);
    }

    /** Send a 204 No Content response (used for OPTIONS preflight). */
    public static function noContent(): never
    {
        self::timingHeader();
        http_response_code(204);
        throw new HaltException(204);
    }
}
