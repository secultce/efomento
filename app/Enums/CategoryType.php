<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum CategoryType: string implements HasLabel
{
    use HasOptions;

    case PROJETO = 'PROJETO';
    case EDITAL = 'EDITAL';

    public function label(): string
    {
        return match ($this) {
            self::PROJETO => 'Projeto',
            self::EDITAL => 'Edital',
        };
    }
}
