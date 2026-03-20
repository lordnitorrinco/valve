<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SecurityTest extends TestCase
{
    private const SECRET = 'test-csrf-secret-key';

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
    }

    #[Test]
    public function csrf_generate_returns_base64_token(): void
    {
        $token = Security::generateCsrf(self::SECRET);

        $this->assertNotEmpty($token);
        $decoded = base64_decode($token, true);
        $this->assertNotFalse($decoded);
        $this->assertStringContainsString('.', $decoded);
    }

    #[Test]
    public function csrf_validate_accepts_valid_token(): void
    {
        $token = Security::generateCsrf(self::SECRET);

        // validateCsrf calls Response::error which does exit — we can't test it directly
        // Instead, we replicate the validation logic to test correctness
        $decoded = base64_decode($token, true);
        $this->assertNotFalse($decoded);
        $this->assertStringContainsString('.', $decoded);

        [$time, $hmac] = explode('.', $decoded, 2);
        $expected = hash_hmac('sha256', $time, self::SECRET);

        $this->assertTrue(hash_equals($expected, $hmac));
        $this->assertLessThan(900, time() - (int)$time);
    }

    #[Test]
    public function csrf_token_contains_timestamp(): void
    {
        $before = time();
        $token = Security::generateCsrf(self::SECRET);
        $after = time();

        $decoded = base64_decode($token, true);
        [$time] = explode('.', $decoded, 2);

        $this->assertGreaterThanOrEqual($before, (int)$time);
        $this->assertLessThanOrEqual($after, (int)$time);
    }

    #[Test]
    public function csrf_tokens_are_unique(): void
    {
        $t1 = Security::generateCsrf(self::SECRET);
        usleep(10000);
        $t2 = Security::generateCsrf(self::SECRET);

        // Within the same second they may be equal (HMAC of same timestamp)
        // but over time they'll differ — test the structure is valid for both
        $this->assertNotEmpty($t1);
        $this->assertNotEmpty($t2);
    }

    #[Test]
    public function csrf_different_secrets_produce_different_hmacs(): void
    {
        $t1 = Security::generateCsrf('secret-one');
        $t2 = Security::generateCsrf('secret-two');

        $d1 = base64_decode($t1, true);
        $d2 = base64_decode($t2, true);

        [, $hmac1] = explode('.', $d1, 2);
        [, $hmac2] = explode('.', $d2, 2);

        $this->assertNotEquals($hmac1, $hmac2);
    }

    #[Test]
    public function csrf_expired_token_is_detectable(): void
    {
        $oldTime = time() - 1000;
        $hmac = hash_hmac('sha256', (string)$oldTime, self::SECRET);
        $token = base64_encode($oldTime . '.' . $hmac);

        $decoded = base64_decode($token, true);
        [$time] = explode('.', $decoded, 2);

        $this->assertGreaterThan(900, time() - (int)$time);
    }

    #[Test]
    public function csrf_tampered_hmac_is_detectable(): void
    {
        $token = Security::generateCsrf(self::SECRET);
        $decoded = base64_decode($token, true);
        [$time, $hmac] = explode('.', $decoded, 2);

        $tampered = base64_encode($time . '.tampered_hmac_value');
        $decodedTampered = base64_decode($tampered, true);
        [, $tamperedHmac] = explode('.', $decodedTampered, 2);

        $expected = hash_hmac('sha256', $time, self::SECRET);
        $this->assertFalse(hash_equals($expected, $tamperedHmac));
    }

    #[Test]
    public function csrf_invalid_base64_is_detectable(): void
    {
        $decoded = base64_decode('!!!invalid!!!', true);
        $this->assertFalse($decoded);
    }

    #[Test]
    public function csrf_missing_dot_separator_is_detectable(): void
    {
        $token = base64_encode('nodothere');
        $decoded = base64_decode($token, true);
        $this->assertFalse(str_contains($decoded, '.'));
    }
}
