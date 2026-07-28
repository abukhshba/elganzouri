<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'current_quantity',
        'reserved_quantity',
        'average_cost',
        'stock_value',
        'last_purchase_price',
        'last_sale_price',
        'minimum_quantity',
        'maximum_quantity',
        'reorder_quantity',
        'last_transaction_id',
    ];

    protected $casts = [
        'current_quantity' => DecimalCast::class,
        'reserved_quantity' => DecimalCast::class,
        'average_cost' => DecimalCast::class,
        'stock_value' => DecimalCast::class,
        'last_purchase_price' => DecimalCast::class,
        'last_sale_price' => DecimalCast::class,
        'minimum_quantity' => DecimalCast::class,
        'maximum_quantity' => DecimalCast::class,
        'reorder_quantity' => DecimalCast::class,
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get real-time available stock quantity (Current Qty - Reserved Qty).
     */
    public function getAvailableQuantityAttribute(): float
    {
        return round($this->current_quantity - $this->reserved_quantity, 4);
    }

    /**
     * Scope query to low stock items (where current quantity is below or equal to re-order alert level).
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_quantity', '<=', 'minimum_quantity')
            ->where('minimum_quantity', '>', 0);
    }
}
