<?php

namespace App\Services;

use App\Models\MeasurementUnitDetail;
use InvalidArgumentException;

class UomConversionService
{
    /**
     * Resolve the conversion factor to convert quantity from given Detail to the default unit.
     */
    public function resolveConversionFactor(MeasurementUnitDetail $detail): float
    {
        return (float) $detail->value;
    }

    /**
     * Convert quantity in target unit to quantity in default unit.
     */
    public function convertToBaseQuantity(MeasurementUnitDetail $detail, float $quantity): float
    {
        $factor = $this->resolveConversionFactor($detail);

        return $quantity * $factor;
    }

    /**
     * Calculate cost per default unit from target unit price.
     */
    public function calculateBaseUnitCost(MeasurementUnitDetail $detail, float $unitPrice): float
    {
        $factor = $this->resolveConversionFactor($detail);

        if ($factor <= 0) {
            throw new InvalidArgumentException("Conversion factor must be greater than zero.");
        }

        return $unitPrice / $factor;
    }
}
