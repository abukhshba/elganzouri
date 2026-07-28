<?php

namespace App\Enums;

use ArchTech\Enums\Options;

enum SaleStatus: string
{
    use Options;

    case DRAFT = 'DRAFT';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
}
