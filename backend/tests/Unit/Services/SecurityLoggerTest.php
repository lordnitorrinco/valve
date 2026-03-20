<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for SecurityLogger.
 * Asserts JSON log shape, timestamps, user-agent truncation, missing $_SERVER fallbacks, and Unicode context.
 */
class SecurityLoggerTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'seclog_');
        ini_set('error_log', $this->logFile);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $_SERVER['HTTP_USER_AGENT'] = 'TestBrowser/1.0';
    }

    protected function tearDown(): void
    {
        ini_restore('error_log');
        @unlink($this->logFile);
    }

    /** Tests that log writes a [SECURITY] JSON line with event, IP, UA, and context. */
    #[Test]
    public function log_writes_json_to_error_log(): void
    {
        SecurityLogger::log('test_event', ['extra_key' => 'extra_value']);

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('[SECURITY]', $content);
        $this->assertStringContainsString('"event":"test_event"', $content);
        $this->assertStringContainsString('"ip":"192.168.1.1"', $content);
        $this->assertStringContainsString('"extra_key":"extra_value"', $content);
        $this->assertStringContainsString('TestBrowser', $content);
    }

    /** Tests that each log line includes an ISO-style timestamp field. */
    #[Test]
    public function log_includes_timestamp(): void
    {
        SecurityLogger::log('time_test');

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('"time":"', $content);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T/', $content);
    }

    /** Tests that an overly long User-Agent is truncated in the logged JSON. */
    #[Test]
    public function log_truncates_long_user_agent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = str_repeat('A', 500);

        SecurityLogger::log('ua_test');

        $content = file_get_contents($this->logFile);
        // Extract the JSON from the log line
        preg_match('/\[SECURITY\] (.+)/', $content, $matches);
        $this->assertNotEmpty($matches);

        $data = json_decode($matches[1], true);
        $this->assertNotNull($data);
        $this->assertLessThanOrEqual(200, strlen($data['ua']));
    }

    /** Tests that missing REMOTE_ADDR and HTTP_USER_AGENT log as "unknown". */
    #[Test]
    public function log_handles_missing_server_vars(): void
    {
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);

        SecurityLogger::log('no_server_vars');

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('"ip":"unknown"', $content);
        $this->assertStringContainsString('"ua":"unknown"', $content);
    }

    /** Tests that Unicode characters in context are preserved in the log output. */
    #[Test]
    public function log_handles_unicode_context(): void
    {
        SecurityLogger::log('unicode_test', ['name' => 'García Ñoño']);

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('García Ñoño', $content);
    }
}
