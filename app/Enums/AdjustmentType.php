<?php

namespace App\Enums;

use ArchTech\Enums\Options;

enum AdjustmentType: string
{
    use Options;

    case IN = 'IN';
    case OUT = 'OUT';
}
