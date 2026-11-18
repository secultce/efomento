<?php

namespace App\Enums;

enum DocumentPhase: string
{
    case OPENING = 'opening';
    case JURIDICAL = 'juridical';
    case FORMALIZATION = 'formalization';
    case BUDGET = 'budget';
    case PAYMENT = 'payment';
    case MONITORING = 'monitoring';
}
