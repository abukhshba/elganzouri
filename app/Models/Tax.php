<?php

namespace App\Models;

use App\Casts\DecimalCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

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
