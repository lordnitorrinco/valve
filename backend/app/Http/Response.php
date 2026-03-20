<?php

/**
 * HTTP response helper.
 *
 * Provides a consistent JSON API response format and handles
 * CORS headers for cross-origin requests from the frontend.
 */
class Response
{
    /**
     * Set CORS headers to allow requests from the frontend origin.
     * Called on every request (including OPTIONS preflight).
     *
     * @param string $allowedOrigin  The permitted origin domain
     */
    public static function cors(string $allowedOrigin = '*'): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Send a JSON response and terminate execution.
     *
     * @param array $data    Response payload
     * @param int   $status  HTTP status code
     */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    /**
     * Send a 200 OK JSON response.
     */
    public static function success(array $data): never
    {
        self::json($data, 200);
    }

    /**
     * Send an error JSON response with the given status code.
     *
     * @param string $message  Human-readable error description
     * @param int    $status   HTTP error code (default: 400)
     * @param array  $extra    Additional fields merged into the response
     */
    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(array_merge(['error' => $message], $extra), $status);
    }

    /**
     * Send a 204 No Content response (used for OPTIONS preflight).
     */
    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }
}
