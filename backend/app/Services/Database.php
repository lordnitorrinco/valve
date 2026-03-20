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

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['name'],
            $config['charset']
        );

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                self::$instance = new PDO($dsn, $config['user'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // Use real prepared statements
                ]);
                return self::$instance;
            } catch (PDOException $e) {
                if ($attempt === $maxRetries) throw $e;
                sleep(2); // Wait before retrying
            }
        }

        throw new RuntimeException('Could not connect to database');
    }
}
