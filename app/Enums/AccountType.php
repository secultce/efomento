<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum AccountType: string implements HasLabel
{
    use HasOptions;

    case CORRENTE = 'CORRENTE';
    case POUPANCA = 'POUPANCA';

    public function label(): string
    {
        return match ($this) {
            self::CORRENTE => 'Conta Corrente',
            self::POUPANCA => 'Conta Poupança',
        };
    }
}
