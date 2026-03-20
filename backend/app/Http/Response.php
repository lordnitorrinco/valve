<?php

class Response
{
    public static function cors(string $allowedOrigin = '*'): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    public static function success(array $data): never
    {
        self::json($data, 200);
    }

    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(array_merge(['error' => $message], $extra), $status);
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }
}
