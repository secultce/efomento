<?php

namespace App\Exceptions\Domain;

use App\Exceptions\AppException;

final class DomainAuthorizationException extends AppException
{
    public function __construct(
        string $userMessage,
        array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($userMessage, httpStatus: 403, context: $context, previous: $previous);
    }
}
