<?php

namespace App\Enums;

enum DocumentType: string
{
    case TERM = 'term';
    case EXTRACT = 'extract';
    case JURIDICAL_OPINION = 'juridical_opinion';
    case DISPATCH = 'dispatch';
    case CI = 'ci';

    public function label(): string
    {
        return match ($this) {
            self::TERM => 'TC',
            self::EXTRACT => 'EX',
            self::JURIDICAL_OPINION => 'PJ',
            self::DISPATCH => 'PO',
            self::CI => 'CI',
        };
    }
}
