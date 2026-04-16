<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum ResponsibleSector: string implements HasLabel
{
    use HasOptions;

    case C_FINALISTICA = 'c_finalistica';
    case ASJUR = 'asjur';
    case CODIP = 'codip';
    case CEGEF = 'cegef';
    case MONITORAMENTO = 'monitoramento';

    public function label(): string
    {
        return match ($this) {
            self::C_FINALISTICA => 'Coordenadoria Finalística',
            self::ASJUR => 'ASJUR',
            self::CODIP => 'CODIP',
            self::CEGEF => 'CEGEF',
            self::MONITORAMENTO => 'Monitoramento',
        };
    }
}
