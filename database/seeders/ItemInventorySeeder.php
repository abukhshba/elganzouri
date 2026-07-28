<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ItemInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = Item::all();
        $warehouses = Warehouse::all();

        foreach ($warehouses as $warehouse) {
            foreach ($items as $item) {
                ItemInventory::firstOrCreate(
                    [
                        'item_id' => $item->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    [
                        'current_quantity' => 0.0000,
                        'reserved_quantity' => 0.0000,
                        'average_cost' => 0.0000,
                        'stock_value' => 0.0000,
                        'minimum_quantity' => $item->min_stock_alert,
                    ]
                );
            }
        }
    }
}
