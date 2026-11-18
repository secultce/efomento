<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum CgeAtendeStatus: string implements HasLabel
{
    use HasOptions;

    case ABERTO = 'ABERTO';
    case FINALIZADO = 'FINALIZADO';

    public function label(): string
    {
        return match ($this) {
            self::ABERTO => 'Aberto',
            self::FINALIZADO => 'Finalizado',
        };
    }
}
