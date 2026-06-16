<?php

namespace App\Enums;

enum DocumentType: string
{
    case CI = 'ci';
    case TC = 'tc';
    case ET = 'et';
    case PJ = 'pj';
    case PO = 'po';
    case D = 'd';

    public function label(): string
    {
        return match ($this) {
            self::CI => 'CI',
            self::TC => 'TC',
            self::ET => 'ET',
            self::PJ => 'PJ',
            self::PO => 'PO',
            self::D => 'D',
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
            self::D => 'Despacho',
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
            self::D => DocumentPhase::PAYMENT,
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
