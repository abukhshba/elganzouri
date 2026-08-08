<?php

namespace App\Models;

use App\Traits\HasBilingualFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class PaymentTerm extends Model
{
    use HasFactory, SoftDeletes, HasTranslations, HasBilingualFields;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'days_due',
        'description',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'days_due' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function getDueDaysAttribute(): ?int
    {
        return $this->days_due;
    }

    public function setDueDaysAttribute($value): void
    {
        $this->attributes['days_due'] = $value;
    }
}
