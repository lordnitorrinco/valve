<?php

/**
 * Minimal HTTP router.
 *
 * Supports GET and POST routes with exact path matching.
 * Rejects unsupported HTTP methods with 405 and unknown routes with 404.
 */
class Router
{
    /** @var array<string, array<string, callable>> Method => [path => handler] */
    private array $routes = [];

    /**
     * Register a GET route handler.
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Register a POST route handler.
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Match the current request against registered routes and execute the handler.
     *
     * Terminates with 405 for disallowed methods or 404 for unmatched paths.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!in_array($method, ['GET', 'POST', 'OPTIONS'], true)) {
            Response::error('Method not allowed', 405);
        }

        $handler = $this->routes[$method][$uri] ?? null;

        if ($handler) {
            $handler();
        } else {
            Response::error('Not found', 404);
        }
    }
}
