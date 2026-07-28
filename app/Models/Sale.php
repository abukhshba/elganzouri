<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Traits\GeneratesDocumentCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory, GeneratesDocumentCode;

    protected string $documentCodeColumn = 'invoice_number';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'warehouse_id',
        'cashbox_id',
        'status',
        'payment_status',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'paid_amount',
        'due_amount',
        'total_cogs',
        'total_profit',
        'issue_date',
        'due_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'status' => SaleStatus::class,
        'payment_status' => PaymentStatus::class,
        'total_amount' => DecimalCast::class,
        'tax_amount' => DecimalCast::class,
        'discount_amount' => DecimalCast::class,
        'paid_amount' => DecimalCast::class,
        'due_amount' => DecimalCast::class,
        'total_cogs' => DecimalCast::class,
        'total_profit' => DecimalCast::class,
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function getDocumentType(): string
    {
        return 'SALE';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
