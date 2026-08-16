<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity' => DecimalCast::class,
        'unit_price' => DecimalCast::class,
        'unit_cost' => DecimalCast::class,
        'conversion_factor' => DecimalCast::class,
        'base_quantity' => DecimalCast::class,
        'line_total' => DecimalCast::class,
        'line_cogs' => DecimalCast::class,
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function measurementUnitDetail(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnitDetail::class, 'measurement_unit_detail_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
