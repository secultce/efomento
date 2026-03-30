<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum ReportStatus: string implements HasLabel
{
    use HasOptions;
    case SEM_CADASTRO = 'SEM CADASTRO';
    case REGULAR_E_ADIMPLEMTE = 'REGULAR E ADIMPLEMTE';
    case REGULAR_E_INADIMPLENTE = 'REGULAR E INADIMPLENTE';
    case IRREGULAR_E_ADIMPLENTE = 'IRREGULAR E ADIMPLENTE';
    case IRREGULAR_E_INADIMPLENTE = 'IRREGULAR E INADIMPLENTE';
    public function label(): string
    {
        return match ($this) {
            self::SEM_CADASTRO => 'Sem Cadastro',
            self::REGULAR_E_ADIMPLEMTE => 'Regular e Adimplente',
            self::REGULAR_E_INADIMPLENTE => 'Regular e Inadimplente',
            self::IRREGULAR_E_ADIMPLENTE => 'Irregular e Adimplente',
            self::IRREGULAR_E_INADIMPLENTE => 'Irregular e Inadimplente',
        };
    }

}
