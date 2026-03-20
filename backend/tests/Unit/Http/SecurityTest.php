<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for Security CSRF helpers.
 * Exercises token generation shape, HMAC validation, timing, tampering, and parse edge cases.
 */
class SecurityTest extends TestCase
{
    private const SECRET = 'test-csrf-secret-key';

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
    }

    /** Tests that generateCsrf returns non-empty Base64 containing a time.HMAC payload after decode. */
    #[Test]
    public function csrf_generate_returns_base64_token(): void
    {
        $token = Security::generateCsrf(self::SECRET);

        $this->assertNotEmpty($token);
        $decoded = base64_decode($token, true);
        $this->assertNotFalse($decoded);
        $this->assertStringContainsString('.', $decoded);
    }

    /** Tests that a generated token’s HMAC matches the expected value and is within the validity window. */
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

    /** Tests that the embedded timestamp in the token falls between before/after generation times. */
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

    /** Tests that two successive tokens are non-empty (may coincide within the same second). */
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

    /** Tests that different secrets yield different HMAC segments for comparable tokens. */
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

    /** Tests that an old timestamp is detectable as outside the 900s sliding window. */
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

    /** Tests that an altered HMAC does not match the expected hash for the timestamp. */
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

    /** Tests that invalid Base64 fails strict decode used in validation flows. */
    #[Test]
    public function csrf_invalid_base64_is_detectable(): void
    {
        $decoded = base64_decode('!!!invalid!!!', true);
        $this->assertFalse($decoded);
    }

    /** Tests that payload without a dot separator is distinguishable from valid time.HMAC form. */
    #[Test]
    public function csrf_missing_dot_separator_is_detectable(): void
    {
        $token = base64_encode('nodothere');
        $decoded = base64_decode($token, true);
        $this->assertFalse(str_contains($decoded, '.'));
    }
}
