<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Unit extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'short_name',
    ];
}
