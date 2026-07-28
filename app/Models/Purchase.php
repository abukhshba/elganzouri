<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Traits\GeneratesDocumentCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory, GeneratesDocumentCode;

    protected string $documentCodeColumn = 'purchase_number';

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'warehouse_id',
        'status',
        'payment_status',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'paid_amount',
        'due_amount',
        'issue_date',
        'due_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'status' => PurchaseStatus::class,
        'payment_status' => PaymentStatus::class,
        'total_amount' => DecimalCast::class,
        'tax_amount' => DecimalCast::class,
        'discount_amount' => DecimalCast::class,
        'paid_amount' => DecimalCast::class,
        'due_amount' => DecimalCast::class,
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function getDocumentType(): string
    {
        return 'PURCHASE';
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
        return $this->hasMany(PurchaseItem::class);
    }
}
