<?php

namespace App\Exceptions\Domain;

use App\Exceptions\AppException;

final class ExpiredTwoFactorCodeException extends AppException
{
    public function __construct()
    {
        parent::__construct('O código expirou ou a solicitação não é mais válida. Entre novamente.');
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
