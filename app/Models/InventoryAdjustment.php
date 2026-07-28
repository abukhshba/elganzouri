<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;

use App\Traits\GeneratesDocumentCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAdjustment extends Model
{
    use HasFactory, GeneratesDocumentCode;

    protected string $documentCodeColumn = 'adjustment_number';

    protected $fillable = [
        'adjustment_number',
        'warehouse_id',
        'adjustment_type',
        'reason',
        'status',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'adjustment_type' => AdjustmentType::class,
        'reason' => AdjustmentReason::class,
    ];

    public function getDocumentType(): string
    {
        return 'ADJUSTMENT';
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentItem::class);
    }
}
