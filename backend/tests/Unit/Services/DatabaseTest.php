<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Subclass that connects via SQLite, bypassing MySQL.
 */
class SqliteDatabase extends Database
{
    protected static function createPdo(string $dsn, string $user, string $password): PDO
    {
        return new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    protected static function retrySleep(): void {}
}

/**
 * Subclass that fails on the first attempt, then succeeds.
 * Tests the retry + sleep path.
 */
class RetryDatabase extends Database
{
    private static int $callCount = 0;

    public static function resetCallCount(): void { self::$callCount = 0; }

    protected static function createPdo(string $dsn, string $user, string $password): PDO
    {
        self::$callCount++;
        if (self::$callCount === 1) {
            throw new PDOException('Simulated transient failure');
        }
        return new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    protected static function retrySleep(): void {}
}

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        $ref = new ReflectionClass(Database::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        RetryDatabase::resetCallCount();
    }

    #[Test]
    public function connect_throws_on_invalid_config(): void
    {
        $this->expectException(PDOException::class);
        Database::connect([
            'host' => '127.0.0.1', 'name' => 'x', 'user' => 'x',
            'password' => 'x', 'charset' => 'utf8mb4',
        ], 1);
    }

    #[Test]
    public function connect_returns_singleton_when_preset(): void
    {
        $ref = new ReflectionClass(Database::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $mock = $this->createMock(PDO::class);
        $prop->setValue(null, $mock);

        $result = Database::connect([
            'host' => 'x', 'name' => 'x', 'user' => 'x',
            'password' => 'x', 'charset' => 'utf8mb4',
        ]);
        $this->assertSame($mock, $result);
    }

    #[Test]
    public function connect_succeeds_with_sqlite_dsn(): void
    {
        $pdo = Database::connect([
            'dsn' => 'sqlite::memory:',
            'user' => '',
            'password' => '',
        ]);
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    #[Test]
    public function connect_caches_and_returns_same_instance(): void
    {
        $cfg = ['dsn' => 'sqlite::memory:', 'user' => '', 'password' => ''];
        $first  = Database::connect($cfg);
        $second = Database::connect($cfg);
        $this->assertSame($first, $second);
    }

    #[Test]
    public function reset_singleton_allows_new_connection(): void
    {
        $cfg = ['dsn' => 'sqlite::memory:', 'user' => '', 'password' => ''];
        $first = Database::connect($cfg);

        $ref = new ReflectionClass(Database::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $second = Database::connect($cfg);
        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function connect_retries_and_succeeds_on_second_attempt(): void
    {
        $pdo = RetryDatabase::connect([
            'dsn' => 'sqlite::memory:',
            'user' => '',
            'password' => '',
        ], 3);
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    #[Test]
    public function retrySleep_actually_sleeps(): void
    {
        $ref = new ReflectionMethod(Database::class, 'retrySleep');
        $ref->setAccessible(true);

        $before = microtime(true);
        $ref->invoke(null);
        $elapsed = microtime(true) - $before;

        $this->assertGreaterThanOrEqual(1.5, $elapsed);
    }

    #[Test]
    public function connect_throws_last_exception_after_all_retries_fail(): void
    {
        $this->expectException(PDOException::class);
        Database::connect([
            'host' => '127.0.0.1', 'name' => 'nope',
            'user' => 'no', 'password' => 'no', 'charset' => 'utf8mb4',
        ], 1);
    }
}
