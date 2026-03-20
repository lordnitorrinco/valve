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

    // ── dispatch() ──────────────────────────────────────────────────

    #[Test]
    public function dispatch_calls_matching_get_handler(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';

        $router = new Router();
        $called = false;
        $router->get('/test', function () use (&$called) {
            $called = true;
        });

        $router->dispatch();
        $this->assertTrue($called);
    }

    #[Test]
    public function dispatch_calls_matching_post_handler(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/submit';

        $router = new Router();
        $called = false;
        $router->post('/api/submit', function () use (&$called) {
            $called = true;
        });

        $router->dispatch();
        $this->assertTrue($called);
    }

    #[Test]
    public function dispatch_returns_404_for_unknown_route(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/nonexistent';

        $router = new Router();
        $router->get('/test', function () {});

        try {
            $router->dispatch();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(404, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('Not found', $decoded['error']);
        }
    }

    #[Test]
    public function dispatch_returns_405_for_unsupported_method(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/test';

        $router = new Router();
        $router->get('/test', function () {});

        try {
            $router->dispatch();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(405, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('Method not allowed', $decoded['error']);
        }
    }

    #[Test]
    public function dispatch_returns_405_for_delete_method(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/test';

        $router = new Router();

        try {
            $router->dispatch();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(405, $e->statusCode);
        }
    }

    #[Test]
    public function dispatch_matches_parameterized_route(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/submissions/42/cv';

        $router = new Router();
        $capturedParams = null;
        $router->get('/api/submissions/{id}/cv', function (array $params) use (&$capturedParams) {
            $capturedParams = $params;
        });

        $router->dispatch();
        $this->assertSame('42', $capturedParams['id']);
    }

    #[Test]
    public function dispatch_strips_query_string_from_uri(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test?foo=bar&baz=1';

        $router = new Router();
        $called = false;
        $router->get('/test', function () use (&$called) {
            $called = true;
        });

        $router->dispatch();
        $this->assertTrue($called);
    }

    #[Test]
    public function dispatch_allows_options_but_returns_404_without_handler(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['REQUEST_URI'] = '/api/submit';

        $router = new Router();
        $router->post('/api/submit', function () {});

        try {
            $router->dispatch();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(404, $e->statusCode);
        }
    }

    #[Test]
    public function dispatch_returns_404_when_no_routes_registered(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/anything';

        $router = new Router();

        try {
            $router->dispatch();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(404, $e->statusCode);
        }
    }
}
