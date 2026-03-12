<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum Gender: string implements HasLabel
{
    use HasOptions;
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

}