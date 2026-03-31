<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT             = 'draft';
    case PENDING_SIGNATURE = 'pending_signature';
    case SIGNED            = 'signed';
}
