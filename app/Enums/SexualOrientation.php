<?php

namespace App\Enums;

enum SexualOrientation: string
{
    case LESBICA = 'LESBICA';
    case GAY = 'GAY';
    case HETEROSEXUAL = 'HETEROSEXUAL';
    case BISSEXUAL = 'BISSEXUAL';
    case OUTRA = 'OUTRA';
    case PREFERE_NAO_RESPONDER = 'PREFERE_NAO_RESPONDER';

    public function label(): string
    {
        return match ($this) {
            self::LESBICA => 'Lésbica',
            self::GAY => 'Gay',
            self::HETEROSEXUAL => 'Heterossexual',
            self::BISSEXUAL => 'Bissexual',
            self::OUTRA => 'Outra',
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