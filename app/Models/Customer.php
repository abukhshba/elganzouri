<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_number',
        'payment_term_id',
        'credit_limit',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => DecimalCast::class,
        'balance' => DecimalCast::class,
        'is_active' => 'boolean',
    ];

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
