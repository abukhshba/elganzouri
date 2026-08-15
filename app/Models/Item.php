<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Traits\HasBilingualFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Item extends Model
{
    use HasFactory, SoftDeletes, HasTranslations, HasBilingualFields;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'category_id',
        'brand_id',
        'measurement_unit_id',
        'sku',
        'barcode',
        'name',
        'description',
        'min_stock_alert',
        'is_active',
    ];

    protected $casts = [
        'min_stock_alert' => DecimalCast::class,
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(ItemInventory::class);
    }
}
