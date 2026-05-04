<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum ReportStatus: string implements HasLabel
{
    use HasOptions;
    case SEM_CADASTRO = 'SEM_CADASTRO';
    case REGULAR_E_ADIMPLENTE = 'REGULAR E_ADIMPLENTE';
    case REGULAR_E_INADIMPLENTE = 'REGULAR E_INADIMPLENTE';
    case IRREGULAR_E_ADIMPLENTE = 'IRREGULAR E_ADIMPLENTE';
    case IRREGULAR_E_INADIMPLENTE = 'IRREGULAR E_INADIMPLENTE';

    public function label(): string
    {
        return match ($this) {
            self::SEM_CADASTRO => 'Sem Cadastro',
            self::REGULAR_E_ADIMPLENTE => 'Regular e Adimplente',
            self::REGULAR_E_INADIMPLENTE => 'Regular e Inadimplente',
            self::IRREGULAR_E_ADIMPLENTE => 'Irregular e Adimplente',
            self::IRREGULAR_E_INADIMPLENTE => 'Irregular e Inadimplente',
        };
    }
}
