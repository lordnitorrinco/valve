<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the Validator helper.
 * Covers required, email, maxLength, pattern, and url rules, chaining, and per-field error precedence.
 */
class ValidatorTest extends TestCase
{
    private Validator $v;

    protected function setUp(): void
    {
        $this->v = new Validator();
    }

    /** Tests that required passes when the value is non-empty after trimming. */
    #[Test]
    public function required_passes_with_non_empty_value(): void
    {
        $this->v->required('name', 'John', 'Required');
        $this->assertFalse($this->v->fails());
        $this->assertEmpty($this->v->errors());
    }

    /** Tests that required fails and records the message when the value is null. */
    #[Test]
    public function required_fails_with_null(): void
    {
        $this->v->required('name', null, 'Name is required');
        $this->assertTrue($this->v->fails());
        $this->assertEquals(['name' => 'Name is required'], $this->v->errors());
    }

    /** Tests that required fails for an empty string. */
    #[Test]
    public function required_fails_with_empty_string(): void
    {
        $this->v->required('name', '', 'Required');
        $this->assertTrue($this->v->fails());
    }

    /** Tests that required fails when the value is only whitespace. */
    #[Test]
    public function required_fails_with_whitespace_only(): void
    {
        $this->v->required('name', '   ', 'Required');
        $this->assertTrue($this->v->fails());
    }

    /** Tests that email passes for a syntactically valid address. */
    #[Test]
    public function email_passes_with_valid_email(): void
    {
        $this->v->email('email', 'user@example.com', 'Invalid');
        $this->assertFalse($this->v->fails());
    }

    /** Tests that email fails and stores the custom message for an invalid address. */
    #[Test]
    public function email_fails_with_invalid_email(): void
    {
        $this->v->email('email', 'not-an-email', 'Invalid email');
        $this->assertTrue($this->v->fails());
        $this->assertEquals(['email' => 'Invalid email'], $this->v->errors());
    }

    /** Tests that email does not run when the value is empty (optional field semantics). */
    #[Test]
    public function email_skips_empty_value(): void
    {
        $this->v->email('email', '', 'Invalid');
        $this->assertFalse($this->v->fails());
    }

    /** Tests that maxLength passes when the string is shorter than the limit. */
    #[Test]
    public function maxLength_passes_within_limit(): void
    {
        $this->v->maxLength('name', 'abc', 5, 'Too long');
        $this->assertFalse($this->v->fails());
    }

    /** Tests that maxLength passes when length equals the limit exactly. */
    #[Test]
    public function maxLength_passes_at_exact_limit(): void
    {
        $this->v->maxLength('name', 'abcde', 5, 'Too long');
        $this->assertFalse($this->v->fails());
    }

    /** Tests that maxLength fails when the string exceeds the limit. */
    #[Test]
    public function maxLength_fails_over_limit(): void
    {
        $this->v->maxLength('name', 'abcdef', 5, 'Too long');
        $this->assertTrue($this->v->fails());
    }

    /** Tests that maxLength uses multibyte-safe length for Unicode vs ASCII limits. */
    #[Test]
    public function maxLength_handles_multibyte_characters(): void
    {
        $this->v->maxLength('name', 'áéíóú', 5, 'Too long');
        $this->assertFalse($this->v->fails());

        $v2 = new Validator();
        $v2->maxLength('name', 'áéíóúñ', 5, 'Too long');
        $this->assertTrue($v2->fails());
    }

    /** Tests that pattern passes when the value matches the regex. */
    #[Test]
    public function pattern_passes_with_matching_regex(): void
    {
        $this->v->pattern('phone', '+34 600000000', '/^[\d\s\-+()]{7,30}$/', 'Bad format');
        $this->assertFalse($this->v->fails());
    }

    /** Tests that pattern fails when the value does not match the regex. */
    #[Test]
    public function pattern_fails_with_non_matching_regex(): void
    {
        $this->v->pattern('phone', 'abc', '/^\d+$/', 'Numbers only');
        $this->assertTrue($this->v->fails());
    }

    /** Tests that pattern skips validation when the value is empty. */
    #[Test]
    public function pattern_skips_empty_value(): void
    {
        $this->v->pattern('phone', '', '/^\d+$/', 'Error');
        $this->assertFalse($this->v->fails());
    }

    /** Tests that url passes for a well-formed HTTP(S) URL. */
    #[Test]
    public function url_passes_with_valid_url(): void
    {
        $this->v->url('site', 'https://linkedin.com/in/user', 'Invalid URL');
        $this->assertFalse($this->v->fails());
    }

    /** Tests that url fails for strings that are not valid URLs. */
    #[Test]
    public function url_fails_with_invalid_url(): void
    {
        $this->v->url('site', 'not-a-url', 'Invalid URL');
        $this->assertTrue($this->v->fails());
    }

    /** Tests that url does not validate when the value is empty. */
    #[Test]
    public function url_skips_empty_value(): void
    {
        $this->v->url('site', '', 'Invalid URL');
        $this->assertFalse($this->v->fails());
    }

    /** Tests that multiple rules append distinct field errors when chained. */
    #[Test]
    public function chained_validations_collect_multiple_errors(): void
    {
        $this->v
            ->required('name', '', 'Name required')
            ->required('email', '', 'Email required')
            ->email('email', 'bad', 'Invalid email');

        $this->assertTrue($this->v->fails());
        $errors = $this->v->errors();
        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    /** Tests that the first failing rule on a field wins (later rules do not overwrite). */
    #[Test]
    public function first_error_per_field_wins(): void
    {
        $this->v
            ->required('email', '', 'Email required')
            ->email('email', '', 'Invalid email');

        $this->assertEquals('Email required', $this->v->errors()['email']);
    }
}
