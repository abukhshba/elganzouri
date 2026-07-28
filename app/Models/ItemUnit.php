<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'unit_id',
        'conversion_factor',
        'barcode',
        'purchase_price',
        'sale_price',
        'is_default_purchase',
        'is_default_sale',
    ];

    protected $casts = [
        'conversion_factor' => DecimalCast::class,
        'purchase_price' => DecimalCast::class,
        'sale_price' => DecimalCast::class,
        'is_default_purchase' => 'boolean',
        'is_default_sale' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
