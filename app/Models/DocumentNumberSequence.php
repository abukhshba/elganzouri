<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentNumberSequence extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'padding' => 'integer',
        'current_number' => 'integer',
        'last_reset_at' => 'date',
    ];
}
