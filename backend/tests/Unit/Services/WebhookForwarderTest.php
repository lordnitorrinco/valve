<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WebhookForwarderTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'whlog_');
        ini_set('error_log', $this->logFile);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
    }

    protected function tearDown(): void
    {
        ini_restore('error_log');
        @unlink($this->logFile);
    }

    #[Test]
    public function forward_with_empty_url_does_nothing(): void
    {
        WebhookForwarder::forward(['test' => 'data'], '');

        $content = file_get_contents($this->logFile);
        $this->assertEmpty(trim($content), 'No log should be generated for empty URL');
    }

    #[Test]
    public function forward_logs_failure_for_unreachable_url(): void
    {
        $data = [
            'firstName' => 'Test',
            'email'     => 'test@example.com',
        ];

        WebhookForwarder::forward($data, 'http://192.0.2.1:1/nonexistent');

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('webhook_failed', $content);
    }

    #[Test]
    public function forward_does_not_include_security_fields(): void
    {
        $data = [
            'firstName' => 'Test',
            'email'     => 'test@example.com',
        ];

        $this->assertArrayNotHasKey('website', $data);
        $this->assertArrayNotHasKey('csrfToken', $data);
        $this->assertArrayNotHasKey('X-CSRF-Token', $data);
    }

    #[Test]
    public function forward_sends_all_form_data(): void
    {
        $data = [
            'firstName' => 'Pablo',
            'lastName'  => 'García',
            'email'     => 'pablo@test.com',
            'phone'     => '600123456',
        ];

        $json = json_encode($data);
        $this->assertStringContainsString('Pablo', $json);
        $this->assertStringContainsString('pablo@test.com', $json);
        $this->assertCount(4, $data);
    }
}
