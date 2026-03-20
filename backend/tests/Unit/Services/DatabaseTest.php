<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        $reflection = new ReflectionClass(Database::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

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
