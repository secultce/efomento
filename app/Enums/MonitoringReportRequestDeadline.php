<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum MonitoringReportRequestDeadline: string implements HasLabel
{
    use HasOptions;

    case PNAB = 'PNAB';
    case LEI_ALDIR_BLANC = 'LEI_ALDIR_BLANC';
    case LEI_PAULO_GUSTAVO = 'LEI_PAULO_GUSTAVO';
    case CICLOS_CALENDARIZADOS = 'CICLOS_CALENDARIZADOS';
    case MECENAS = 'MECENAS';

    public function days(): int
    {
        return match ($this) {
            self::PNAB, self::LEI_ALDIR_BLANC, self::LEI_PAULO_GUSTAVO => 120,
            self::CICLOS_CALENDARIZADOS => 90,
            self::MECENAS => 240,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::LEI_ALDIR_BLANC => 'Lei Aldir Blanc',
            self::LEI_PAULO_GUSTAVO => 'Lei Paulo Gustavo',
            self::CICLOS_CALENDARIZADOS => 'Ciclos calendarizados',
            self::MECENAS => 'Mecenas',
            default => $this->value,
        };
    }
}
