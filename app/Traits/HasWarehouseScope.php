<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasWarehouseScope
{
    /**
     * Scope query to a specific warehouse ID.
     */
    public function scopeWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where($this->getTable() . '.warehouse_id', $warehouseId);
    }

    /**
     * Scope query to active warehouses only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.is_active', true);
    }
}
