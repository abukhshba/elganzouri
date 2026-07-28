<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Tax extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'code',
        'rate_percentage',
        'is_active',
    ];

    protected $casts = [
        'rate_percentage' => DecimalCast::class,
        'is_active' => 'boolean',
    ];
}
