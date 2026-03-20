<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for HaltException.
 * Verifies constructor stores statusCode and body, extends RuntimeException,
 * and maps fields to parent getMessage()/getCode().
 */
class HaltExceptionTest extends TestCase
{
    #[Test]
    public function stores_status_code_and_body(): void
    {
        $e = new HaltException(200, '{"ok":true}');

        $this->assertSame(200, $e->statusCode);
        $this->assertSame('{"ok":true}', $e->body);
    }

    #[Test]
    public function body_defaults_to_empty_string(): void
    {
        $e = new HaltException(204);

        $this->assertSame(204, $e->statusCode);
        $this->assertSame('', $e->body);
    }

    #[Test]
    public function extends_runtime_exception(): void
    {
        $e = new HaltException(500, 'error');

        $this->assertInstanceOf(RuntimeException::class, $e);
    }

    #[Test]
    public function parent_code_matches_status_code(): void
    {
        $e = new HaltException(422, 'body');

        $this->assertSame(422, $e->getCode());
    }

    #[Test]
    public function parent_message_matches_body(): void
    {
        $e = new HaltException(400, 'some body');

        $this->assertSame('some body', $e->getMessage());
    }
}
