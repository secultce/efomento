<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum RaceColor: string implements HasLabel
{
    use HasOptions;
    case BRANCA = 'BRANCA';
    case PRETA = 'PRETA';
    case AMARELA = 'AMARELA';
    case PARDA = 'PARDA';
    case INDIGENA = 'INDIGENA';

    public function label(): string
    {
        return match ($this) {
            self::BRANCA => 'Branca',
            self::PRETA => 'Preta',
            self::AMARELA => 'Amarela',
            self::PARDA => 'Parda',
            self::INDIGENA => 'Indígena',
        };
    }
}
