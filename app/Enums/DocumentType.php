<?php

namespace App\Enums;

enum DocumentType: string
{
    case TC = 'tc';
    case ET = 'et';
    case PJ = 'pj';
    case D = 'd';
    case CI = 'ci';

    public function label(): string
    {
        return match ($this) {
            self::TC => 'TC',
            self::ET => 'ET',
            self::PJ => 'PJ',
            self::D => 'D',
            self::CI => 'CI',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::TC => 'Termo de Execução Cultural',
            self::ET => 'Extrato',
            self::PJ => 'Parecer Jurídico',
            self::D => 'Despacho',
            self::CI => 'Comunicação Interna',
        };
    }

    public function phase(): DocumentPhase
    {
        return match ($this) {
            self::CI => DocumentPhase::OPENING,
            self::TC,
            self::ET,
            self::PJ => DocumentPhase::FORMALIZATION,
            self::D => DocumentPhase::JURIDICAL,
        };
    }

    public static function requiredForFormalizationAdvance(): array
    {
        return [
            self::TC,
            self::ET,
            self::PJ,
        ];
    }
}
