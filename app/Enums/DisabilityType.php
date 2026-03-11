<?php

namespace App\Enums;

enum DisabilityType: string
{
    case NAO = 'NAO';
    case AUDITIVA = 'AUDITIVA';
    case FISICA_MOTORA = 'FISICA_MOTORA';
    case INTELECTUAL = 'INTELECTUAL';
    case VISUAL = 'VISUAL';
    case MULTIPLA = 'MULTIPLA';
    case TEA = 'TEA';
    case OUTRA = 'OUTRA';

    public function label(): string
    {
        return match ($this) {
            self::NAO => 'Não',
            self::AUDITIVA => 'Sim, Auditiva',
            self::FISICA_MOTORA => 'Sim, Física-motora',
            self::INTELECTUAL => 'Sim, Intelectual',
            self::VISUAL => 'Sim, Visual',
            self::MULTIPLA => 'Sim, Múltipla',
            self::TEA => 'Sim, Transtorno do Espectro Autista',
            self::OUTRA => 'Sim, Outra',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])
            ->toArray();
    }
}