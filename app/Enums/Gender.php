<?php

namespace App\Enums;

enum Gender: string
{
    case MASCULINO = 'MASCULINO';
    case FEMININO = 'FEMININO';
    case OUTRO = 'OUTRO';
    case PREFERE_NAO_RESPONDER = 'PREFERE_NAO_RESPONDER';

    public function label(): string
    {
        return match ($this) {
            self::MASCULINO => 'Masculino',
            self::FEMININO => 'Feminino',
            self::OUTRO => 'Outro',
            self::PREFERE_NAO_RESPONDER => 'Prefere não responder',
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