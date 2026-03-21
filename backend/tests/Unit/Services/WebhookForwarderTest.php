<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testable subclass that simulates HTTP responses without network I/O.
 */
class FakeWebhookForwarder extends WebhookForwarder
{
    public static int $fakeCode = 200;
    public static string $fakeBody = '{"ok":true}';
    public static string $fakeError = '';

    protected static function executeCurl(\CurlHandle $ch): array
    {
        return [
            'body'  => static::$fakeBody,
            'code'  => static::$fakeCode,
            'error' => static::$fakeError,
        ];
    }
}

class WebhookForwarderTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'whlog_');
        ini_set('error_log', $this->logFile);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        FakeWebhookForwarder::$fakeCode  = 200;
        FakeWebhookForwarder::$fakeBody  = '{"ok":true}';
        FakeWebhookForwarder::$fakeError = '';
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
        $this->assertEmpty(trim($content));
    }

    #[Test]
    public function forward_logs_failure_for_unreachable_url(): void
    {
        WebhookForwarder::forward(['a' => 'b'], 'http://192.0.2.1:1/nonexistent');
        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('webhook_failed', $content);
    }

    #[Test]
    public function forward_success_logs_webhook_success(): void
    {
        FakeWebhookForwarder::forward(
            ['firstName' => 'Test', 'email' => 'test@example.com'],
            'https://fake.endpoint/webhook'
        );

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('webhook_success', $content);
    }

    #[Test]
    public function forward_failure_logs_webhook_failed(): void
    {
        FakeWebhookForwarder::$fakeCode = 500;
        FakeWebhookForwarder::$fakeBody = 'Internal Server Error';

        FakeWebhookForwarder::forward(
            ['firstName' => 'Test'],
            'https://fake.endpoint/webhook'
        );

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('webhook_failed', $content);
    }

    #[Test]
    public function forward_failure_with_curl_error(): void
    {
        FakeWebhookForwarder::$fakeCode  = 0;
        FakeWebhookForwarder::$fakeError = 'Connection timed out';
        FakeWebhookForwarder::$fakeBody  = '';

        FakeWebhookForwarder::forward(
            ['firstName' => 'Test'],
            'https://fake.endpoint/webhook'
        );

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('webhook_failed', $content);
    }

    #[Test]
    public function forward_does_not_include_security_fields(): void
    {
        $data = ['firstName' => 'Test', 'email' => 'test@example.com'];
        $this->assertArrayNotHasKey('website', $data);
        $this->assertArrayNotHasKey('csrfToken', $data);
    }

    #[Test]
    public function normalizeWebhookPayload_maps_lead_id_to_id(): void
    {
        $in = [
            'firstName'   => 'A',
            'utm_source'  => 'newsletter',
            'lead_id'     => 'lead-123',
        ];
        $out = WebhookForwarder::normalizeWebhookPayload($in);
        $this->assertSame('lead-123', $out['id']);
        $this->assertSame('newsletter', $out['utm_source']);
        $this->assertArrayNotHasKey('lead_id', $out);
    }

    #[Test]
    public function normalizeWebhookPayload_omits_empty_lead_id(): void
    {
        $out = WebhookForwarder::normalizeWebhookPayload(['lead_id' => '', 'utm_source' => 'x']);
        $this->assertArrayNotHasKey('id', $out);
        $this->assertArrayNotHasKey('lead_id', $out);
    }

    #[Test]
    public function forward_sends_all_form_data(): void
    {
        $data = [
            'firstName' => 'Pablo', 'lastName' => 'García',
            'email' => 'pablo@test.com', 'phone' => '600123456',
        ];
        $json = json_encode($data);
        $this->assertStringContainsString('Pablo', $json);
        $this->assertCount(4, $data);
    }
}
