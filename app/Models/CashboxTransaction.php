<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Enums\CashboxTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashboxTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'transaction_type' => CashboxTransactionType::class,
        'amount' => DecimalCast::class,
        'balance_before' => DecimalCast::class,
        'balance_after' => DecimalCast::class,
        'created_at' => 'datetime',
    ];

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
