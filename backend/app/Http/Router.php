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
     * Supports exact paths and patterns with {param} placeholders.
     * Terminates with 405 for disallowed methods or 404 for unmatched paths.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!in_array($method, ['GET', 'POST', 'OPTIONS'], true)) {
            Response::error('Method not allowed', 405);
        }

        // Try exact match first
        $handler = $this->routes[$method][$uri] ?? null;
        if ($handler) {
            $handler();
            return;
        }

        // Try pattern matching for routes with {param} placeholders
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            if (!str_contains($pattern, '{')) continue;
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler($params);
                return;
            }
        }

        Response::error('Not found', 404);
    }
}
