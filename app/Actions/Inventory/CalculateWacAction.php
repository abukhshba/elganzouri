<?php

namespace App\Actions\Inventory;

class CalculateWacAction
{
    /**
     * Execute Weighted Average Cost (WAC) recalculation formula for stock inflows.
     */
    public function execute(float $currentQty, float $currentWac, float $incomingQty, float $incomingCost): float
    {
        $totalQuantity = $currentQty + $incomingQty;

        if ($totalQuantity <= 0) {
            return 0.0;
        }

        $existingTotalValuation = $currentQty * $currentWac;
        $incomingTotalValuation = $incomingQty * $incomingCost;

        $newValuation = $existingTotalValuation + $incomingTotalValuation;

        return round($newValuation / $totalQuantity, 4);
    }
}
