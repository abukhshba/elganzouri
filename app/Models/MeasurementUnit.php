<?php

namespace App\Models;

use App\Traits\HasBilingualFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class MeasurementUnit extends Model
{
    use HasFactory, HasTranslations, HasBilingualFields;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'slug',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(MeasurementUnitDetail::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
