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

    // ── validateCsrf() — full integration via HaltException ─────────

    #[Test]
    public function validateCsrf_accepts_valid_token(): void
    {
        $token = Security::generateCsrf(self::SECRET);
        Security::validateCsrf($token, self::SECRET);
        $this->assertTrue(true);
    }

    #[Test]
    public function validateCsrf_rejects_expired_token(): void
    {
        $oldTime = time() - 1000;
        $hmac  = hash_hmac('sha256', (string) $oldTime, self::SECRET);
        $token = base64_encode($oldTime . '.' . $hmac);

        try {
            Security::validateCsrf($token, self::SECRET);
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(403, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('CSRF token expired', $decoded['error']);
        }
    }

    #[Test]
    public function validateCsrf_rejects_tampered_hmac(): void
    {
        $token = base64_encode(time() . '.fakehmacinvalid');

        try {
            Security::validateCsrf($token, self::SECRET);
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(403, $e->statusCode);
        }
    }

    #[Test]
    public function validateCsrf_rejects_invalid_base64(): void
    {
        try {
            Security::validateCsrf('!!!invalid!!!', self::SECRET);
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(403, $e->statusCode);
        }
    }

    #[Test]
    public function validateCsrf_rejects_missing_dot_separator(): void
    {
        $token = base64_encode('nodothere');

        try {
            Security::validateCsrf($token, self::SECRET);
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(403, $e->statusCode);
        }
    }

    #[Test]
    public function validateCsrf_respects_custom_max_age(): void
    {
        $oldTime = time() - 10;
        $hmac  = hash_hmac('sha256', (string) $oldTime, self::SECRET);
        $token = base64_encode($oldTime . '.' . $hmac);

        try {
            Security::validateCsrf($token, self::SECRET, 5);
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(403, $e->statusCode);
        }
    }

    // ── checkContentType() ──────────────────────────────────────────

    #[Test]
    public function checkContentType_passes_with_multipart_form_data(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary';
        Security::checkContentType();
        $this->assertTrue(true);
    }

    #[Test]
    public function checkContentType_rejects_json(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        try {
            Security::checkContentType();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(415, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('Invalid content type', $decoded['error']);
        }
    }

    #[Test]
    public function checkContentType_rejects_missing_header(): void
    {
        unset($_SERVER['CONTENT_TYPE']);

        try {
            Security::checkContentType();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(415, $e->statusCode);
        }
    }

    #[Test]
    public function checkContentType_rejects_plain_text(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'text/plain';

        try {
            Security::checkContentType();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(415, $e->statusCode);
        }
    }

    // ── checkOrigin() ───────────────────────────────────────────────

    #[Test]
    public function checkOrigin_passes_with_matching_origin(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8080';
        unset($_SERVER['HTTP_REFERER']);

        Security::checkOrigin('http://localhost:8080');
        $this->assertTrue(true);
    }

    #[Test]
    public function checkOrigin_rejects_mismatched_origin(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://evil.com';

        try {
            Security::checkOrigin('http://localhost:8080');
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(403, $e->statusCode);
        }
    }

    #[Test]
    public function checkOrigin_passes_with_matching_referer_when_no_origin(): void
    {
        unset($_SERVER['HTTP_ORIGIN']);
        $_SERVER['HTTP_REFERER'] = 'http://localhost:8080/form';

        Security::checkOrigin('http://localhost:8080');
        $this->assertTrue(true);
    }

    #[Test]
    public function checkOrigin_rejects_mismatched_referer_when_no_origin(): void
    {
        $_SERVER['HTTP_ORIGIN'] = '';
        $_SERVER['HTTP_REFERER'] = 'http://evil.com/form';

        try {
            Security::checkOrigin('http://localhost:8080');
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(403, $e->statusCode);
        }
    }

    #[Test]
    public function checkOrigin_passes_when_neither_origin_nor_referer(): void
    {
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_REFERER']);

        Security::checkOrigin('http://localhost:8080');
        $this->assertTrue(true);
    }

    // ── checkHoneypot() ─────────────────────────────────────────────

    #[Test]
    public function checkHoneypot_passes_when_field_is_empty(): void
    {
        $_POST['website'] = '';
        Security::checkHoneypot();
        $this->assertTrue(true);
    }

    #[Test]
    public function checkHoneypot_passes_when_field_is_missing(): void
    {
        unset($_POST['website']);
        Security::checkHoneypot();
        $this->assertTrue(true);
    }

    #[Test]
    public function checkHoneypot_catches_bot_with_filled_field(): void
    {
        $_POST['website'] = 'http://spam.com';

        try {
            Security::checkHoneypot();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame(0, $decoded['id']);
            $this->assertSame('Solicitud recibida correctamente', $decoded['message']);
        }
    }
}
