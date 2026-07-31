<?php

namespace App\Exceptions\Integration;

use App\Exceptions\AppException;

final class ExternalServiceException extends AppException
{
    public static function unavailable(string $service, \Throwable $previous): self
    {
        return new self(
            "Não foi possível se comunicar com {$service}. Tente novamente em instantes.",
            httpStatus: 503,
            context: ['service' => $service],
            previous: $previous,
        );
    }

    public static function fromFailedResponse(string $service, string $message, array $context = []): self
    {
        return new self(
            $message,
            httpStatus: 503,
            context: ['service' => $service, ...$context],
        );
    }
}
