<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class ValidatorTest extends TestCase
{
    private Validator $v;

    protected function setUp(): void
    {
        $this->v = new Validator();
    }

    #[Test]
    public function required_passes_with_non_empty_value(): void
    {
        $this->v->required('name', 'John', 'Required');
        $this->assertFalse($this->v->fails());
        $this->assertEmpty($this->v->errors());
    }

    #[Test]
    public function required_fails_with_null(): void
    {
        $this->v->required('name', null, 'Name is required');
        $this->assertTrue($this->v->fails());
        $this->assertEquals(['name' => 'Name is required'], $this->v->errors());
    }

    #[Test]
    public function required_fails_with_empty_string(): void
    {
        $this->v->required('name', '', 'Required');
        $this->assertTrue($this->v->fails());
    }

    #[Test]
    public function required_fails_with_whitespace_only(): void
    {
        $this->v->required('name', '   ', 'Required');
        $this->assertTrue($this->v->fails());
    }

    #[Test]
    public function email_passes_with_valid_email(): void
    {
        $this->v->email('email', 'user@example.com', 'Invalid');
        $this->assertFalse($this->v->fails());
    }

    #[Test]
    public function email_fails_with_invalid_email(): void
    {
        $this->v->email('email', 'not-an-email', 'Invalid email');
        $this->assertTrue($this->v->fails());
        $this->assertEquals(['email' => 'Invalid email'], $this->v->errors());
    }

    #[Test]
    public function email_skips_empty_value(): void
    {
        $this->v->email('email', '', 'Invalid');
        $this->assertFalse($this->v->fails());
    }

    #[Test]
    public function maxLength_passes_within_limit(): void
    {
        $this->v->maxLength('name', 'abc', 5, 'Too long');
        $this->assertFalse($this->v->fails());
    }

    #[Test]
    public function maxLength_passes_at_exact_limit(): void
    {
        $this->v->maxLength('name', 'abcde', 5, 'Too long');
        $this->assertFalse($this->v->fails());
    }

    #[Test]
    public function maxLength_fails_over_limit(): void
    {
        $this->v->maxLength('name', 'abcdef', 5, 'Too long');
        $this->assertTrue($this->v->fails());
    }

    #[Test]
    public function maxLength_handles_multibyte_characters(): void
    {
        $this->v->maxLength('name', 'áéíóú', 5, 'Too long');
        $this->assertFalse($this->v->fails());

        $v2 = new Validator();
        $v2->maxLength('name', 'áéíóúñ', 5, 'Too long');
        $this->assertTrue($v2->fails());
    }

    #[Test]
    public function pattern_passes_with_matching_regex(): void
    {
        $this->v->pattern('phone', '+34 600000000', '/^[\d\s\-+()]{7,30}$/', 'Bad format');
        $this->assertFalse($this->v->fails());
    }

    #[Test]
    public function pattern_fails_with_non_matching_regex(): void
    {
        $this->v->pattern('phone', 'abc', '/^\d+$/', 'Numbers only');
        $this->assertTrue($this->v->fails());
    }

    #[Test]
    public function pattern_skips_empty_value(): void
    {
        $this->v->pattern('phone', '', '/^\d+$/', 'Error');
        $this->assertFalse($this->v->fails());
    }

    #[Test]
    public function url_passes_with_valid_url(): void
    {
        $this->v->url('site', 'https://linkedin.com/in/user', 'Invalid URL');
        $this->assertFalse($this->v->fails());
    }

    #[Test]
    public function url_fails_with_invalid_url(): void
    {
        $this->v->url('site', 'not-a-url', 'Invalid URL');
        $this->assertTrue($this->v->fails());
    }

    #[Test]
    public function url_skips_empty_value(): void
    {
        $this->v->url('site', '', 'Invalid URL');
        $this->assertFalse($this->v->fails());
    }

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

    #[Test]
    public function first_error_per_field_wins(): void
    {
        $this->v
            ->required('email', '', 'Email required')
            ->email('email', '', 'Invalid email');

        $this->assertEquals('Email required', $this->v->errors()['email']);
    }
}
