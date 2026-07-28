<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'days_due',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'days_due' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];
}
