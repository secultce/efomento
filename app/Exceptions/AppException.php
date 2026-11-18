<?php

namespace App\Exceptions;

abstract class AppException extends \RuntimeException
{
    public function __construct(
        string $userMessage,
        private readonly int $httpStatus = 422,
        private readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($userMessage, 0, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function context(): array
    {
        return $this->context;
    }

    public function shouldReport(): bool
    {
        return true;
    }
}
