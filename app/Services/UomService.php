<?php

namespace App\Services;

use App\Exceptions\InvalidUomConversionException;
use App\Models\MeasurementUnitDetail;

class UomService
{
    /**
     * Get the conversion factor for an item and targeted unit detail ID.
     */
    public function getConversionFactor(int $unitDetailId): float
    {
        $detail = MeasurementUnitDetail::find($unitDetailId);

        if (! $detail || $detail->value <= 0) {
            throw new InvalidUomConversionException(
                "Invalid UOM conversion factor for Unit Detail ID {$unitDetailId}."
            );
        }

        return (float) $detail->value;
    }

    /**
     * Convert quantity in specified unit to default Unit quantity.
     */
    public function convertQuantityToBaseUnit(int $unitDetailId, float $quantity): float
    {
        $factor = $this->getConversionFactor($unitDetailId);

        return round($quantity * $factor, 4);
    }

    /**
     * Convert unit price in specified unit to default Unit price.
     */
    public function convertPriceToBaseUnit(int $unitDetailId, float $price): float
    {
        $factor = $this->getConversionFactor($unitDetailId);

        if ($factor <= 0) {
            throw new InvalidUomConversionException("Conversion factor cannot be zero or negative.");
        }

        return round($price / $factor, 4);
    }
}
