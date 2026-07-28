<?php

namespace App\Enums;

use ArchTech\Enums\Options;

enum PurchaseStatus: string
{
    use Options;

    case DRAFT = 'DRAFT';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
}
