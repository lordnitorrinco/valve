<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for HealthController JSON payload and status codes.
 */
class HealthControllerTest extends TestCase
{
    #[Test]
    public function check_returns_success_when_database_ok(): void
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $dir = sys_get_temp_dir();

        try {
            HealthController::check($pdo, $dir);
            $this->fail('Expected HaltException');
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
            $data = json_decode($e->body, true);
            $this->assertSame('healthy', $data['status']);
            $this->assertSame('ok', $data['database']);
            $this->assertArrayHasKey('php', $data);
            $this->assertArrayHasKey('bytes_free', $data['uploads']);
        }
    }

    #[Test]
    public function check_returns_503_when_database_fails(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willThrowException(new PDOException('boom'));

        try {
            HealthController::check($pdo, sys_get_temp_dir());
            $this->fail('Expected HaltException');
        } catch (HaltException $e) {
            $this->assertSame(503, $e->statusCode);
            $data = json_decode($e->body, true);
            $this->assertStringContainsString('unavailable', $data['error']);
            $this->assertSame('unhealthy', $data['status']);
            $this->assertSame('error', $data['database']);
        }
    }
}
