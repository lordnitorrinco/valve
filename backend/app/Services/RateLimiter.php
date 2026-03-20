<?php

/**
 * Application-level IP-based rate limiter backed by MySQL.
 *
 * Complements the Nginx rate limiter (which operates at the connection level)
 * by tracking individual submission attempts per IP in the database.
 * This provides defense-in-depth against form abuse.
 */
class RateLimiter
{
    private PDO $db;

    /** @var int Maximum allowed attempts within the time window */
    private int $maxAttempts;

    /** @var int Time window size in minutes */
    private int $windowMinutes;

    /**
     * @param PDO $db             Database connection
     * @param int $maxAttempts    Max requests per IP in the window (e.g. 10)
     * @param int $windowMinutes  Window duration in minutes (e.g. 5)
     */
    public function __construct(PDO $db, int $maxAttempts, int $windowMinutes)
    {
        $this->db             = $db;
        $this->maxAttempts    = $maxAttempts;
        $this->windowMinutes  = $windowMinutes;
    }

    /**
     * Check if the given IP has exceeded the rate limit.
     *
     * If under the limit, records the attempt. If over, terminates
     * the request with a 429 Too Many Requests response.
     *
     * @param string $ip  Client IP address
     */
    public function check(string $ip): void
    {
        $this->cleanup();

        // Count recent attempts from this IP
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE ip_address = :ip AND attempted_at > DATE_SUB(NOW(), INTERVAL :window MINUTE)'
        );
        $stmt->execute([':ip' => $ip, ':window' => $this->windowMinutes]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= $this->maxAttempts) {
            SecurityLogger::log('rate_limited', ['ip' => $ip, 'count' => $count]);
            Response::error('Too many requests', 429);
        }

        // Record this attempt
        $stmt = $this->db->prepare('INSERT INTO rate_limits (ip_address) VALUES (:ip)');
        $stmt->execute([':ip' => $ip]);
    }

    /**
     * Remove expired entries to keep the table small.
     * Deletes records older than 2x the window to avoid edge-case gaps.
     */
    private function cleanup(): void
    {
        $this->db->exec(
            'DELETE FROM rate_limits WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ' . ($this->windowMinutes * 2) . ' MINUTE)'
        );
    }
}
