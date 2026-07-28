<?php

namespace App\Enums;

use ArchTech\Enums\Options;

enum CashboxTransactionType: string
{
    use Options;

    case IN = 'IN';
    case OUT = 'OUT';
}
