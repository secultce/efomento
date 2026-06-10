<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum TermStatus: string implements HasLabel
{
    use HasOptions;

    case SIGNED = 'signed';
    case UNSIGNED = 'unsigned';

    public function label(): string
    {
        return match ($this) {
            self::SIGNED => 'Assinado',
            self::UNSIGNED => 'Não assinado',
        };
    }
}
