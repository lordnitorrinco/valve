<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for Database (PDO singleton wrapper).
 * Verifies connection failure on bad credentials and reuse of a pre-set instance.
 */
class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        $reflection = new ReflectionClass(Database::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /** Tests that connect throws PDOException when credentials cannot open the database. */
    #[Test]
    public function connect_throws_on_invalid_config(): void
    {
        $this->expectException(PDOException::class);

        Database::connect([
            'host'     => '127.0.0.1',
            'name'     => 'nonexistent',
            'user'     => 'fake_user',
            'password' => 'fake_pass',
            'charset'  => 'utf8mb4',
        ], 1);
    }

    /** Tests that connect returns the existing singleton when instance is already injected. */
    #[Test]
    public function connect_returns_singleton(): void
    {
        $reflection = new ReflectionClass(Database::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);

        $mockPdo = $this->createMock(PDO::class);
        $prop->setValue(null, $mockPdo);

        $result = Database::connect([
            'host' => 'x', 'name' => 'x', 'user' => 'x', 'password' => 'x', 'charset' => 'utf8mb4',
        ]);

        $this->assertSame($mockPdo, $result);
    }
}
