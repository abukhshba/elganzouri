<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Unit;
use InvalidArgumentException;

class UomConversionService
{
    /**
     * Resolve the conversion factor to convert quantity from given Unit to Item's Base Unit.
     */
    public function resolveConversionFactor(Item $item, Unit $unit): float
    {
        // 1. Direct Base Unit Match
        if ($unit->id === $item->base_unit_id) {
            return 1.0;
        }

        // 2. Tier 2: Item-Specific Conversion Override
        $itemUnit = $item->itemUnits()
            ->where('unit_id', $unit->id)
            ->first();

        if ($itemUnit) {
            return (float) $itemUnit->conversion_factor;
        }

        // 3. Tier 1: Global Conversion Ratio
        if (! $unit->is_custom_per_item && (float) $unit->global_conversion_factor > 0) {
            return (float) $unit->global_conversion_factor;
        }

        throw new InvalidArgumentException(
            "Unit [{$unit->short_name}] is custom per item and has no conversion ratio configured for Item [{$item->sku}]."
        );
    }

    /**
     * Convert quantity in target unit to quantity in Base Unit.
     */
    public function convertToBaseQuantity(Item $item, Unit $unit, float $quantity): float
    {
        $factor = $this->resolveConversionFactor($item, $unit);

        return $quantity * $factor;
    }

    /**
     * Calculate cost per Base Unit from target unit price.
     */
    public function calculateBaseUnitCost(Item $item, Unit $unit, float $unitPrice): float
    {
        $factor = $this->resolveConversionFactor($item, $unit);

        if ($factor <= 0) {
            throw new InvalidArgumentException("Conversion factor must be greater than zero.");
        }

        return $unitPrice / $factor;
    }
}
