<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone4ATest extends TestCase
{
    use RefreshDatabase;

    public function test_item_inventory_snapshot_creation_and_available_quantity(): void
    {
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-SNAP-01',
            'name' => 'Snapshot Test Item',
        ]);

        $inventory = ItemInventory::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'current_quantity' => 100.0000,
            'reserved_quantity' => 15.0000,
            'average_cost' => 12.5000,
            'stock_value' => 1250.0000,
            'minimum_quantity' => 20.0000,
        ]);

        $this->assertEquals(100.0, $inventory->current_quantity);
        $this->assertEquals(15.0, $inventory->reserved_quantity);
        $this->assertEquals(85.0, $inventory->available_quantity);
        $this->assertEquals(12.5, $inventory->average_cost);
    }

    public function test_unique_item_warehouse_constraint(): void
    {
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-SNAP-02',
            'name' => 'Duplicate Test Item',
        ]);

        ItemInventory::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'current_quantity' => 10.0000,
        ]);

        $this->expectException(QueryException::class);

        ItemInventory::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'current_quantity' => 20.0000,
        ]);
    }

    public function test_low_stock_query_scope(): void
    {
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);

        $item1 = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'I1', 'name' => 'Item 1']);
        $item2 = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'I2', 'name' => 'Item 2']);

        // Item 1: Stock 5 <= Min 10 (Low Stock)
        ItemInventory::create(['item_id' => $item1->id, 'warehouse_id' => $warehouse->id, 'current_quantity' => 5.0, 'minimum_quantity' => 10.0]);

        // Item 2: Stock 50 > Min 10 (Normal Stock)
        ItemInventory::create(['item_id' => $item2->id, 'warehouse_id' => $warehouse->id, 'current_quantity' => 50.0, 'minimum_quantity' => 10.0]);

        $lowStockItems = ItemInventory::lowStock()->get();

        $this->assertCount(1, $lowStockItems);
        $this->assertEquals($item1->id, $lowStockItems->first()->item_id);
    }
}
