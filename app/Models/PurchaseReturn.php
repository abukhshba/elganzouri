<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Enums\ReturnStatus;
use App\Traits\GeneratesDocumentCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory, GeneratesDocumentCode;

    protected string $documentCodeColumn = 'return_number';

    protected $fillable = [
        'return_number',
        'purchase_id',
        'supplier_id',
        'warehouse_id',
        'status',
        'total_amount',
        'refunded_amount',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'status' => ReturnStatus::class,
        'total_amount' => DecimalCast::class,
        'refunded_amount' => DecimalCast::class,
    ];

    public function getDocumentType(): string
    {
        return 'PURCHASE_RETURN';
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
