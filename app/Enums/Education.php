<?php

namespace App\Enums;

enum Education: string
{
    case FUNDAMENTAL = 'FUNDAMENTAL';
    case MEDIO = 'MEDIO';
    case SUPERIOR = 'SUPERIOR';
    case POS_GRADUACAO = 'POS_GRADUACAO';

    public function label(): string
    {
        return match ($this) {
            self::FUNDAMENTAL => 'Ensino Fundamental',
            self::MEDIO => 'Ensino Médio',
            self::SUPERIOR => 'Ensino Superior',
            self::POS_GRADUACAO => 'Pós-graduação',
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