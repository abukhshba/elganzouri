<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Traits\GeneratesDocumentCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Go-live stock document: posts OPENING inventory movements, no money involved.
 */
class OpeningBalance extends Model
{
    use GeneratesDocumentCode;

    protected string $documentCodeColumn = 'opening_number';

    protected $guarded = [];

    protected $casts = [
        'total_value' => DecimalCast::class,
        'issue_date' => 'date',
    ];

    public function getDocumentType(): string
    {
        return 'OPENING';
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
        return $this->hasMany(OpeningBalanceItem::class);
    }
}
