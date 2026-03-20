<?php

/**
 * MySQL database connection singleton.
 *
 * Provides a single PDO instance reused across the request lifecycle.
 * Includes retry logic to handle Docker container startup delays
 * where MySQL may not be immediately available.
 */
class Database
{
    /** @var PDO|null Cached connection instance */
    private static ?PDO $instance = null;

    /**
     * Connect to MySQL and return a PDO instance.
     *
     * On first call, attempts to connect with exponential retries.
     * Subsequent calls return the cached connection (singleton).
     *
     * @param  array $config      Keys: host, name, user, password, charset
     * @param  int   $maxRetries  Number of connection attempts before giving up
     * @return PDO                Active database connection
     * @throws PDOException       If all retry attempts fail
     */
    public static function connect(array $config, int $maxRetries = 10): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dsn = $config['dsn'] ?? sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['name'],
            $config['charset']
        );

        $lastException = null;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                self::$instance = static::createPdo($dsn, $config['user'], $config['password']);
                return self::$instance;
            } catch (PDOException $e) {
                $lastException = $e;
                if ($attempt < $maxRetries) {
                    static::retrySleep();
                }
            }
        }

        throw $lastException ?? new RuntimeException('Could not connect to database');
    }

    /**
     * Wait between retry attempts. Extracted for testability.
     */
    protected static function retrySleep(): void
    {
        sleep(2);
    }

    /**
     * Instantiate a PDO connection. Extracted for testability via subclass override.
     */
    protected static function createPdo(string $dsn, string $user, string $password): PDO
    {
        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
