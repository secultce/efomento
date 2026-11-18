<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum FileStatus: string implements HasLabel
{
    use HasOptions;

    case VALID = 'valid';
    case INVALID = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::VALID => 'De acordo',
            self::INVALID => 'Necessita de ajuste',
        };
    }
}
