<?php

namespace App\Enums;

use ArchTech\Enums\Options;

enum TransferStatus: string
{
    use Options;

    case DRAFT = 'DRAFT';
    case SHIPPED = 'SHIPPED';
    case RECEIVED = 'RECEIVED';
    case CANCELLED = 'CANCELLED';
}
