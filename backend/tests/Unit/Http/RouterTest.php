<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for the Router.
 * Verifies GET/POST registration and coexistence of multiple routes via reflected route table.
 */
class RouterTest extends TestCase
{
    /** Tests that router->get stores the path under GET in the internal routes array. */
    #[Test]
    public function get_route_is_registered(): void
    {
        $router = new Router();
        $called = false;

        $router->get('/test', function () use (&$called) {
            $called = true;
        });

        $reflection = new ReflectionClass($router);
        $prop = $reflection->getProperty('routes');
        $prop->setAccessible(true);
        $routes = $prop->getValue($router);

        $this->assertArrayHasKey('GET', $routes);
        $this->assertArrayHasKey('/test', $routes['GET']);
    }

    /** Tests that router->post stores the path under POST in the internal routes array. */
    #[Test]
    public function post_route_is_registered(): void
    {
        $router = new Router();

        $router->post('/api/submit', function () {});

        $reflection = new ReflectionClass($router);
        $prop = $reflection->getProperty('routes');
        $prop->setAccessible(true);
        $routes = $prop->getValue($router);

        $this->assertArrayHasKey('POST', $routes);
        $this->assertArrayHasKey('/api/submit', $routes['POST']);
    }

    /** Tests that multiple GET and POST routes can be registered without overwriting unrelated entries. */
    #[Test]
    public function multiple_routes_coexist(): void
    {
        $router = new Router();
        $router->get('/a', function () {});
        $router->get('/b', function () {});
        $router->post('/c', function () {});

        $reflection = new ReflectionClass($router);
        $prop = $reflection->getProperty('routes');
        $prop->setAccessible(true);
        $routes = $prop->getValue($router);

        $this->assertCount(2, $routes['GET']);
        $this->assertCount(1, $routes['POST']);
    }

    /** Tests that routes with {param} placeholders are stored in the route table. */
    #[Test]
    public function parameterized_route_is_registered(): void
    {
        $router = new Router();
        $router->get('/api/submissions/{id}/cv', function (array $params) {});

        $reflection = new ReflectionClass($router);
        $prop = $reflection->getProperty('routes');
        $prop->setAccessible(true);
        $routes = $prop->getValue($router);

        $this->assertArrayHasKey('/api/submissions/{id}/cv', $routes['GET']);
    }
}
