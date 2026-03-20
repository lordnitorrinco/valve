<?php

class RateLimiter
{
    private PDO $db;
    private int $maxAttempts;
    private int $windowMinutes;

    public function __construct(PDO $db, int $maxAttempts, int $windowMinutes)
    {
        $this->db             = $db;
        $this->maxAttempts    = $maxAttempts;
        $this->windowMinutes  = $windowMinutes;
    }

    public function check(string $ip): void
    {
        $this->cleanup();

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE ip_address = :ip AND attempted_at > DATE_SUB(NOW(), INTERVAL :window MINUTE)'
        );
        $stmt->execute([':ip' => $ip, ':window' => $this->windowMinutes]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= $this->maxAttempts) {
            SecurityLogger::log('rate_limited', ['ip' => $ip, 'count' => $count]);
            Response::error('Too many requests', 429);
        }

        $stmt = $this->db->prepare('INSERT INTO rate_limits (ip_address) VALUES (:ip)');
        $stmt->execute([':ip' => $ip]);
    }

    private function cleanup(): void
    {
        $this->db->exec(
            'DELETE FROM rate_limits WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ' . ($this->windowMinutes * 2) . ' MINUTE)'
        );
    }
}
