<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentNumberSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'prefix',
        'suffix',
        'padding',
        'current_number',
        'reset_period',
        'last_reset_at',
    ];

    protected $casts = [
        'padding' => 'integer',
        'current_number' => 'integer',
        'last_reset_at' => 'date',
    ];
}
