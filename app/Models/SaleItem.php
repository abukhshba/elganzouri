<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => DecimalCast::class,
        'unit_price' => DecimalCast::class,
        'conversion_factor' => DecimalCast::class,
        'base_quantity' => DecimalCast::class,
        'base_unit_cost' => DecimalCast::class,
        'line_total' => DecimalCast::class,
        'line_cogs' => DecimalCast::class,
        'line_profit' => DecimalCast::class,
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function measurementUnitDetail(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnitDetail::class, 'measurement_unit_detail_id');
    }




    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
