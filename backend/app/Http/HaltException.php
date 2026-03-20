<?php

/**
 * Thrown by Response methods to halt request processing.
 *
 * Replaces direct exit() calls so that all code paths are unit-testable.
 * The front controller (index.php) catches this and sends the HTTP response.
 */
class HaltException extends \RuntimeException
{
    public int $statusCode;
    public string $body;

    public function __construct(int $statusCode, string $body = '')
    {
        parent::__construct($body, $statusCode);
        $this->statusCode = $statusCode;
        $this->body       = $body;
    }
}
