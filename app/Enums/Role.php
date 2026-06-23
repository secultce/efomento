<?php

namespace App\Enums;

enum Role: string
{
    case FOMENTATION = 'fomentation';
    case COORD_FOMENTATION = 'coord_fomentation';
    case FINANCIAL = 'financial';
    case COORD_FINANCIAL = 'coord_financial';
    case LEGAL_ANALYSIS = 'legal_analysis';
    case COORD_LEGAL = 'coord_legal';
    case BUDGETARY = 'budgetary';
    case COORD_BUDGETARY = 'coord_budgetary';
    case MONITORING = 'monitoring';
    case COORD_MONITORING = 'coord_monitoring';
    case TRACKING = 'tracking';
    case SUPER_ADMIN = 'super_admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // ── Grupos por etapa ──────────────────────────────────────────────────────

    public static function fomentoRoles(): array
    {
        return [self::FOMENTATION->value, self::COORD_FOMENTATION->value, self::SUPER_ADMIN->value];
    }

    public static function legalAnalysisRoles(): array
    {
        return [self::LEGAL_ANALYSIS->value, self::COORD_LEGAL->value, self::COORD_FINANCIAL->value, self::SUPER_ADMIN->value];
    }

    public static function formalizationRoles(): array
    {
        return [self::LEGAL_ANALYSIS->value, self::COORD_LEGAL->value, self::SUPER_ADMIN->value];
    }

    public static function budgetRoles(): array
    {
        return [self::BUDGETARY->value, self::COORD_BUDGETARY->value, self::SUPER_ADMIN->value];
    }

    public static function paymentRoles(): array
    {
        return [self::FINANCIAL->value, self::COORD_FINANCIAL->value, self::SUPER_ADMIN->value];
    }

    public static function monitoringRoles(): array
    {
        return [self::MONITORING->value, self::COORD_MONITORING->value, self::SUPER_ADMIN->value];
    }
}
