<?php

namespace App\Enums;

enum DocumentType: string
{
    case CI = 'ci';
    case TC = 'tc';
    case ET = 'et';
    case PJ = 'pj';
    case PO = 'po';

    public function label(): string
    {
        return match ($this) {
            self::CI => 'CI',
            self::TC => 'TC',
            self::ET => 'ET',
            self::PJ => 'PJ',
            self::PO => 'PO',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::CI => 'Comunicação Interna',
            self::TC => 'Termo de Execução Cultural',
            self::ET => 'Extrato',
            self::PJ => 'Parecer Jurídico',
            self::PO => 'Parecer Orçamentário',
        };
    }

    public function phase(): DocumentPhase
    {
        return match ($this) {
            self::CI => DocumentPhase::OPENING,
            self::TC,
            self::ET,
            self::PJ => DocumentPhase::FORMALIZATION,
            self::PO => DocumentPhase::BUDGET,
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
