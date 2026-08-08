<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Traits\HasBilingualFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Unit extends Model
{
    use HasFactory, HasTranslations, HasBilingualFields;

    public array $translatable = ['name'];

    protected $fillable = [
        'unit_group_id',
        'name',
        'short_name',
        'is_base',
        'is_custom_per_item',
        'global_conversion_factor',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'is_custom_per_item' => 'boolean',
        'global_conversion_factor' => DecimalCast::class,
    ];

    public function unitGroup(): BelongsTo
    {
        return $this->belongsTo(UnitGroup::class, 'unit_group_id');
    }

    public function itemUnits(): HasMany
    {
        return $this->hasMany(ItemUnit::class, 'unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'unit_group_id', 'unit_group_id')
            ->where('is_base', false);
    }
}
