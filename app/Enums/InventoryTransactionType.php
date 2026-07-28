<?php

namespace App\Enums;

use ArchTech\Enums\Options;

enum InventoryTransactionType: string
{
    use Options;

    case IN = 'IN';
    case OUT = 'OUT';
    case TRANSFER_IN = 'TRANSFER_IN';
    case TRANSFER_OUT = 'TRANSFER_OUT';
    case ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    case ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    case RETURN_IN = 'RETURN_IN';
    case RETURN_OUT = 'RETURN_OUT';

    public function isInflow(): bool
    {
        return match ($this) {
            self::IN, self::TRANSFER_IN, self::ADJUSTMENT_IN, self::RETURN_IN => true,
            default => false,
        };
    }

    public function isOutflow(): bool
    {
        return ! $this->isInflow();
    }
}
