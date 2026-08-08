<?php

namespace App\Models;

use App\Traits\HasBilingualFields;
use App\Traits\HasWarehouseScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes, HasWarehouseScope, HasTranslations, HasBilingualFields;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'code',
        'name',
        'phone',
        'address',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventories(): HasMany
    {
        return $this->hasMany(ItemInventory::class);
    }
}
