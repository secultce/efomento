<?php

namespace App\Support;

class Cpf
{
    public static function normalize(?string $value): ?string
    {
        $cpf = preg_replace('/\D+/', '', (string) $value);

        if ($cpf === '') {
            return null;
        }

        return strlen($cpf) === 11 ? $cpf : null;
    }
}
