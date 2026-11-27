<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum ExpenseNature: string implements HasLabel
{
    use HasOptions;

    case CONTRIBUICOES_SEM_FINS_LUCRATIVOS = 'CONTRIBUICOES_SEM_FINS_LUCRATIVOS';
    case CONTRIBUICOES_COM_FINS_LUCRATIVOS = 'CONTRIBUICOES_COM_FINS_LUCRATIVOS';
    case AUXILIO_FINANCEIRO_PESSOA_FISICA = 'AUXILIO_FINANCEIRO_PESSOA_FISICA';

    public function code(): string
    {
        return match ($this) {
            self::CONTRIBUICOES_SEM_FINS_LUCRATIVOS => '335041',
            self::CONTRIBUICOES_COM_FINS_LUCRATIVOS => '336041',
            self::AUXILIO_FINANCEIRO_PESSOA_FISICA => '339048',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::CONTRIBUICOES_SEM_FINS_LUCRATIVOS => '335041 - Contribuições (instituições privadas sem fins lucrativos)',
            self::CONTRIBUICOES_COM_FINS_LUCRATIVOS => '336041 - Contribuições (instituições privadas com fins lucrativos)',
            self::AUXILIO_FINANCEIRO_PESSOA_FISICA => '339048 - Outros auxílios financeiros a pessoas físicas',
        };
    }

    public static function fromExpenseElement(?string $value): ?self
    {
        if ($value === null || ! preg_match('/^\s*(\d[\d.]*)/', $value, $matches)) {
            return null;
        }

        $code = preg_replace('/\D/', '', $matches[1]);

        foreach (self::cases() as $case) {
            if ($case->code() === $code) {
                return $case;
            }
        }

        return null;
    }
}
