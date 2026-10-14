<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningBalanceItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => DecimalCast::class,
        'unit_cost' => DecimalCast::class,
        'selling_price' => DecimalCast::class,
        'conversion_factor' => DecimalCast::class,
        'base_quantity' => DecimalCast::class,
        'base_unit_cost' => DecimalCast::class,
        'line_total' => DecimalCast::class,
    ];

    public function openingBalance(): BelongsTo
    {
        return $this->belongsTo(OpeningBalance::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function measurementUnitDetail(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnitDetail::class);
    }
}
