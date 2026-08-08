<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cashbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'warehouse_id',
        'user_id',
        'current_balance',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'current_balance' => DecimalCast::class,
        'is_active' => 'boolean',
    ];

    public function getBalanceAttribute(): float
    {
        return (float) $this->current_balance;
    }

    public function setBalanceAttribute($value): void
    {
        $this->attributes['current_balance'] = $value;
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashboxTransaction::class);
    }
}
