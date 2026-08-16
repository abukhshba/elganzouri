<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity' => DecimalCast::class,
        'unit_price' => DecimalCast::class,
        'conversion_factor' => DecimalCast::class,
        'base_quantity' => DecimalCast::class,
        'line_total' => DecimalCast::class,
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
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
