<?php

namespace App\Enums;

use ArchTech\Enums\Options;

enum AdjustmentReason: string
{
    use Options;

    case AUDIT_DISCREPANCY = 'AUDIT_DISCREPANCY';
    case DAMAGED = 'DAMAGED';
    case EXPIRED = 'EXPIRED';
    case FOUND = 'FOUND';
    case THEFT_LOSS = 'THEFT_LOSS';
    case OTHER = 'OTHER';
}
