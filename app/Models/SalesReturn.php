<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Enums\ReturnStatus;
use App\Traits\GeneratesDocumentCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasFactory, GeneratesDocumentCode;

    protected string $documentCodeColumn = 'return_number';

    protected $fillable = [
        'return_number',
        'sale_id',
        'customer_id',
        'warehouse_id',
        'cashbox_id',
        'status',
        'total_amount',
        'refunded_amount',
        'total_cogs',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'status' => ReturnStatus::class,
        'total_amount' => DecimalCast::class,
        'refunded_amount' => DecimalCast::class,
        'total_cogs' => DecimalCast::class,
    ];

    public function getDocumentType(): string
    {
        return 'SALES_RETURN';
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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
        return $this->hasMany(SalesReturnItem::class);
    }
}
