<?php

/**
 * Generic, chainable field validator.
 *
 * Each method returns $this so rules can be fluently composed:
 *   (new Validator)->required('name', $v, 'msg')->email('email', $v2, 'msg');
 *
 * Errors are collected per field; the first error per field wins.
 */
class Validator
{
    /** @var array<string, string> Field name => error message */
    private array $errors = [];

    /**
     * Fail if the value is null, empty, or whitespace-only.
     */
    public function required(string $field, ?string $value, string $message): self
    {
        if (empty(trim($value ?? ''))) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /**
     * Fail if the value is present but not a valid email address.
     * Skipped when the value is empty (use required() to enforce presence).
     */
    public function email(string $field, ?string $value, string $message): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /**
     * Fail if the value exceeds $max characters (multibyte-safe).
     */
    public function maxLength(string $field, ?string $value, int $max, string $message): self
    {
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /**
     * Fail if the value does not match the given regex pattern.
     */
    public function pattern(string $field, ?string $value, string $regex, string $message): self
    {
        if (!empty($value) && !preg_match($regex, $value)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /**
     * Fail if the value is present but not a valid URL.
     */
    public function url(string $field, ?string $value, string $message): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /**
     * Returns true if any validation rule has failed.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Returns the collected error messages keyed by field name.
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
