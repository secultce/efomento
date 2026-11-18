<?php

namespace App\Support;

class DocumentNumber
{
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        return in_array(strlen($digits), [11, 14]) ? $digits : null;
    }
}
