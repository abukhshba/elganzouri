<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Enums\InventoryTransactionType;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    public $timestamps = false; // Uses immutable created_at timestamp

    protected $fillable = [
        'item_inventory_id',
        'warehouse_id',
        'item_id',
        'base_unit_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_after',
        'average_cost_after',
        'reference_type',
        'reference_id',
        'performed_by',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'transaction_type' => InventoryTransactionType::class,
        'quantity' => DecimalCast::class,
        'unit_cost' => DecimalCast::class,
        'total_cost' => DecimalCast::class,
        'balance_after' => DecimalCast::class,
        'average_cost_after' => DecimalCast::class,
        'created_at' => 'datetime',
    ];

    public function itemInventory(): BelongsTo
    {
        return $this->belongsTo(ItemInventory::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
