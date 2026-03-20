<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for Response.
 * Every terminal method throws HaltException; tests catch it
 * and verify the status code and JSON body.
 */
class ResponseTest extends TestCase
{
    #[Test]
    public function json_throws_halt_with_encoded_data_and_status(): void
    {
        try {
            @Response::json(['foo' => 'bar'], 200);
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
            $this->assertSame('{"foo":"bar"}', $e->body);
            return;
        }
        $this->fail('HaltException was not thrown');
    }

    #[Test]
    public function json_uses_given_status_code(): void
    {
        try {
            @Response::json(['x' => 1], 422);
        } catch (HaltException $e) {
            $this->assertSame(422, $e->statusCode);
            return;
        }
        $this->fail('HaltException was not thrown');
    }

    #[Test]
    public function json_defaults_status_to_200(): void
    {
        try {
            @Response::json(['a' => 'b']);
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
            return;
        }
        $this->fail('HaltException was not thrown');
    }

    #[Test]
    public function success_throws_halt_with_200_and_payload(): void
    {
        try {
            @Response::success(['message' => 'ok']);
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('ok', $decoded['message']);
            return;
        }
        $this->fail('HaltException was not thrown');
    }

    #[Test]
    public function error_throws_halt_with_error_key(): void
    {
        try {
            @Response::error('Something went wrong', 400);
        } catch (HaltException $e) {
            $this->assertSame(400, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('Something went wrong', $decoded['error']);
            return;
        }
        $this->fail('HaltException was not thrown');
    }

    #[Test]
    public function error_defaults_status_to_400(): void
    {
        try {
            @Response::error('bad');
        } catch (HaltException $e) {
            $this->assertSame(400, $e->statusCode);
            return;
        }
        $this->fail('HaltException was not thrown');
    }

    #[Test]
    public function error_merges_extra_fields_into_body(): void
    {
        try {
            @Response::error('fail', 422, ['fields' => ['name' => 'required']]);
        } catch (HaltException $e) {
            $decoded = json_decode($e->body, true);
            $this->assertSame('fail', $decoded['error']);
            $this->assertSame(['name' => 'required'], $decoded['fields']);
            return;
        }
        $this->fail('HaltException was not thrown');
    }

    #[Test]
    public function no_content_throws_halt_with_204_and_empty_body(): void
    {
        try {
            @Response::noContent();
        } catch (HaltException $e) {
            $this->assertSame(204, $e->statusCode);
            $this->assertSame('', $e->body);
            return;
        }
        $this->fail('HaltException was not thrown');
    }

    #[Test]
    public function cors_does_not_throw(): void
    {
        @Response::cors('http://example.com');
        $this->assertTrue(true);
    }

    #[Test]
    public function cors_with_default_origin_does_not_throw(): void
    {
        @Response::cors();
        $this->assertTrue(true);
    }
}
