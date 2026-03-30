<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum DeliberationType: string implements HasLabel
{
    use HasOptions;
    
    case MANUAL = 'MANUAL';
    case BATCH_CGE = 'BATCH_CGE';
    case FEC = 'FEC';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::BATCH_CGE => 'Batch CGE',
            self::FEC => 'FEC',
        };
    }

};