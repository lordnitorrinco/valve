<?php

class Validator
{
    private array $errors = [];

    public function required(string $field, ?string $value, string $message): self
    {
        if (empty(trim($value ?? ''))) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function email(string $field, ?string $value, string $message): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function maxLength(string $field, ?string $value, int $max, string $message): self
    {
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function pattern(string $field, ?string $value, string $regex, string $message): self
    {
        if (!empty($value) && !preg_match($regex, $value)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function url(string $field, ?string $value, string $message): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
