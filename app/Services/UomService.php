<?php

namespace App\Services;

use App\Exceptions\InvalidUomConversionException;
use App\Models\Item;
use App\Models\ItemUnit;

class UomService
{
    /**
     * Get the conversion factor for an item and targeted unit ID.
     */
    public function getConversionFactor(int $itemId, int $unitId): float
    {
        $item = Item::findOrFail($itemId);

        // If the targeted unit IS the base unit, factor is strictly 1.0
        if ($item->base_unit_id === $unitId) {
            return 1.0;
        }

        $itemUnit = ItemUnit::where('item_id', $itemId)
            ->where('unit_id', $unitId)
            ->first();

        if (! $itemUnit || $itemUnit->conversion_factor <= 0) {
            throw new InvalidUomConversionException(
                "Invalid UOM conversion factor for Item ID {$itemId} and Unit ID {$unitId}."
            );
        }

        return (float) $itemUnit->conversion_factor;
    }

    /**
     * Convert quantity in specified unit to Base Unit quantity.
     * Formula: Base Qty = Invoice Qty * Conversion Factor
     */
    public function convertQuantityToBaseUnit(int $itemId, int $unitId, float $quantity): float
    {
        $factor = $this->getConversionFactor($itemId, $unitId);

        return round($quantity * $factor, 4);
    }

    /**
     * Convert unit price in specified unit to Base Unit price.
     * Formula: Base Unit Cost = Invoice Unit Price / Conversion Factor
     */
    public function convertPriceToBaseUnit(int $itemId, int $unitId, float $price): float
    {
        $factor = $this->getConversionFactor($itemId, $unitId);

        if ($factor <= 0) {
            throw new InvalidUomConversionException("Conversion factor cannot be zero or negative.");
        }

        return round($price / $factor, 4);
    }
}
