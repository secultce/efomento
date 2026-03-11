<?php

namespace App\Enums;

enum RaceColor: string
{
    case BRANCA = 'BRANCA';
    case PRETA = 'PRETA';
    case AMARELA = 'AMARELA';
    case PARDA = 'PARDA';
    case INDIGENA = 'INDIGENA';

    public function label(): string
    {
        return match ($this) {
            self::BRANCA => 'Branca',
            self::PRETA => 'Preta',
            self::AMARELA => 'Amarela',
            self::PARDA => 'Parda',
            self::INDIGENA => 'Indígena',
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