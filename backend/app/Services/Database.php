<?php

class Database
{
    private static ?PDO $instance = null;

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
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                return self::$instance;
            } catch (PDOException $e) {
                if ($attempt === $maxRetries) throw $e;
                sleep(2);
            }
        }

        throw new RuntimeException('Could not connect to database');
    }
}
