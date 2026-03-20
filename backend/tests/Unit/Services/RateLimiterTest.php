<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for RateLimiter.
 *
 * Uses mock PDO because the production SQL relies on MySQL-specific
 * DATE_SUB/INTERVAL syntax that SQLite cannot parse.
 */
class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
    }

    /**
     * Build mock PDO + statements for the check() flow.
     *
     * @return array{PDO&MockObject, PDOStatement&MockObject, PDOStatement&MockObject}
     */
    private function mockPdo(int $countResult): array
    {
        $pdo = $this->createMock(PDO::class);
        $selectStmt = $this->createMock(PDOStatement::class);
        $insertStmt = $this->createMock(PDOStatement::class);

        $pdo->method('exec')->willReturn(0);

        $selectStmt->method('execute')->willReturn(true);
        $selectStmt->method('fetchColumn')->willReturn($countResult);

        $insertStmt->method('execute')->willReturn(true);

        return [$pdo, $selectStmt, $insertStmt];
    }

    #[Test]
    public function check_allows_request_when_under_limit(): void
    {
        [$pdo, $select, $insert] = $this->mockPdo(0);
        $pdo->method('prepare')
            ->willReturnOnConsecutiveCalls($select, $insert);

        $limiter = new RateLimiter($pdo, 5, 60);
        $limiter->check('192.168.1.1');

        $this->assertTrue(true);
    }

    #[Test]
    public function check_allows_request_when_just_under_limit(): void
    {
        [$pdo, $select, $insert] = $this->mockPdo(4);
        $pdo->method('prepare')
            ->willReturnOnConsecutiveCalls($select, $insert);

        $limiter = new RateLimiter($pdo, 5, 60);
        $limiter->check('192.168.1.1');

        $this->assertTrue(true);
    }

    #[Test]
    public function check_blocks_when_at_limit(): void
    {
        [$pdo, $select] = $this->mockPdo(5);
        $pdo->method('prepare')->willReturn($select);

        $limiter = new RateLimiter($pdo, 5, 60);

        try {
            $limiter->check('192.168.1.1');
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(429, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('Too many requests', $decoded['error']);
        }
    }

    #[Test]
    public function check_blocks_when_over_limit(): void
    {
        [$pdo, $select] = $this->mockPdo(10);
        $pdo->method('prepare')->willReturn($select);

        $limiter = new RateLimiter($pdo, 5, 60);

        try {
            $limiter->check('10.0.0.1');
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(429, $e->statusCode);
        }
    }

    #[Test]
    public function check_inserts_attempt_record(): void
    {
        [$pdo, $select, $insert] = $this->mockPdo(0);

        $insert->expects($this->once())
            ->method('execute')
            ->with([':ip' => '10.0.0.1'])
            ->willReturn(true);

        $pdo->method('prepare')
            ->willReturnOnConsecutiveCalls($select, $insert);

        $limiter = new RateLimiter($pdo, 5, 60);
        $limiter->check('10.0.0.1');
    }

    #[Test]
    public function cleanup_is_called_during_check(): void
    {
        [$pdo, $select, $insert] = $this->mockPdo(0);

        $pdo->expects($this->once())
            ->method('exec')
            ->with($this->stringContains('DELETE FROM rate_limits'))
            ->willReturn(0);

        $pdo->method('prepare')
            ->willReturnOnConsecutiveCalls($select, $insert);

        $limiter = new RateLimiter($pdo, 5, 60);
        $limiter->check('192.168.1.1');
    }

    #[Test]
    public function cleanup_uses_double_window_interval(): void
    {
        [$pdo, $select, $insert] = $this->mockPdo(0);

        $windowMinutes = 10;
        $pdo->expects($this->once())
            ->method('exec')
            ->with($this->stringContains((string) ($windowMinutes * 2)))
            ->willReturn(0);

        $pdo->method('prepare')
            ->willReturnOnConsecutiveCalls($select, $insert);

        $limiter = new RateLimiter($pdo, 5, $windowMinutes);
        $limiter->check('192.168.1.1');
    }

    #[Test]
    public function constructor_stores_configuration(): void
    {
        $pdo = $this->createMock(PDO::class);
        $limiter = new RateLimiter($pdo, 10, 30);

        $ref = new ReflectionClass($limiter);

        $maxProp = $ref->getProperty('maxAttempts');
        $maxProp->setAccessible(true);
        $this->assertSame(10, $maxProp->getValue($limiter));

        $windowProp = $ref->getProperty('windowMinutes');
        $windowProp->setAccessible(true);
        $this->assertSame(30, $windowProp->getValue($limiter));
    }
}
