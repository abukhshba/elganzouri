<?php

namespace App\Enums;

use ArchTech\Enums\Options;

enum PaymentStatus: string
{
    use Options;

    case UNPAID = 'UNPAID';
    case PARTIAL = 'PARTIAL';
    case PAID = 'PAID';
}
