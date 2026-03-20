<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RouterTest extends TestCase
{
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
}
