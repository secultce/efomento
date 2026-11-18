<?php

namespace App\Support;

final class Mask
{
    public static function nup(?string $nup): string
    {
        if (blank($nup)) {
            return 'não informado';
        }

        $digits = preg_replace('/\D/', '', $nup) ?? '';

        if (strlen($digits) !== 17) {
            return $nup;
        }

        return substr($digits, 0, 5)
            .'.'
            .substr($digits, 5, 6)
            .'/'
            .substr($digits, 11, 4)
            .'-'
            .substr($digits, 15, 2);
    }
}
