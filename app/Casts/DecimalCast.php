<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class DecimalCast implements CastsAttributes
{
    /**
     * Cast the given value from database to 4-decimal formatted float.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): float
    {
        return round((float) $value, 4);
    }

    /**
     * Prepare the given value for storage in database.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): float
    {
        return round((float) $value, 4);
    }
}
