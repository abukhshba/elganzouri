<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Traits\HasBilingualFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class MeasurementUnitDetail extends Model
{
    use HasFactory, HasTranslations, HasBilingualFields;

    public array $translatable = ['name'];

    protected $fillable = [
        'measurement_unit_id',
        'name',
        'unit_type',
        'is_default',
        'value',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'value' => DecimalCast::class,
    ];

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
