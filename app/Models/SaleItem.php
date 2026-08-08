<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'item_id',
        'unit_id',
        'warehouse_id',
        'quantity',
        'unit_price',
        'conversion_factor',
        'base_quantity',
        'base_unit_cost',
        'line_total',
        'line_cogs',
        'line_profit',
    ];

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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
