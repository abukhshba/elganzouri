<?php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Traits\GeneratesDocumentCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransfer extends Model
{
    use HasFactory, GeneratesDocumentCode;

    protected string $documentCodeColumn = 'transfer_number';

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'shipped_at',
        'received_at',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'status' => TransferStatus::class,
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function getDocumentType(): string
    {
        return 'TRANSFER';
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class);
    }
}
