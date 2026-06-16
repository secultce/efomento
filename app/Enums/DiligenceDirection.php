<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum DiligenceDirection: string implements HasLabel
{
    use HasOptions;

    case OUTBOUND = 'OUTBOUND';
    case INBOUND = 'INBOUND';

    public function label(): string
    {
        return match ($this) {
            self::OUTBOUND => 'Enviada',
            self::INBOUND => 'Recebida',
        };
    }
}
