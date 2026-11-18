<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum Education: string implements HasLabel
{
    use HasOptions;
    case FUNDAMENTAL = 'FUNDAMENTAL';
    case MEDIO = 'MEDIO';
    case SUPERIOR = 'SUPERIOR';
    case POS_GRADUACAO = 'POS_GRADUACAO';

    public function label(): string
    {
        return match ($this) {
            self::FUNDAMENTAL => 'Ensino Fundamental',
            self::MEDIO => 'Ensino Médio',
            self::SUPERIOR => 'Ensino Superior',
            self::POS_GRADUACAO => 'Pós-graduação',
        };
    }
}
