<?php

namespace App\Enums;

enum DocumentType: string
{
    case TERM             = 'term';
    case EXTRACT          = 'extract';
    case JURIDICAL_OPINION = 'juridical_opinion';
    case DISPATCH         = 'dispatch';
    case CI              = 'CI';
}
