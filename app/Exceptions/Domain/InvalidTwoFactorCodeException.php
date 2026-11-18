<?php

namespace App\Exceptions\Domain;

use App\Exceptions\AppException;

final class InvalidTwoFactorCodeException extends AppException
{
    public function __construct()
    {
        parent::__construct('Código inválido. Confira o código recebido por email.');
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
