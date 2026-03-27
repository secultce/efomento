<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum FileStatus: int implements HasLabel
{
    use HasOptions;

    case VALID = 1;
    case INVALID = 2;

    public function label(): string
    {
        return match ($this) {
            self::VALID => 'De acordo',
            self::INVALID => 'Necessita de ajuste',
        };
    }
}
