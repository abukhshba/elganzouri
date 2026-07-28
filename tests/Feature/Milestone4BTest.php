<?php

namespace Tests\Feature;

use App\Enums\InventoryTransactionType;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryPostingService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone4BTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_posting_service_inflow_calculates_wac_and_updates_snapshot(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-WAC-01',
            'name' => 'WAC Test Item',
        ]);

        $postingService = app(InventoryPostingService::class);

        // Shipment 1: 100 units @ $10.00
        $tx1 = $postingService->post(
            itemId: $item->id,
            warehouseId: $warehouse->id,
            type: InventoryTransactionType::IN,
            quantityBase: 100.0,
            unitCostBase: 10.0,
            referenceModel: $item,
            userId: $user->id,
            notes: 'First Shipment'
        );

        $inventory = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();

        $this->assertEquals(100.0, $inventory->current_quantity);
        $this->assertEquals(10.0, $inventory->average_cost);
        $this->assertEquals(1000.0, $inventory->stock_value);
        $this->assertEquals(100.0, $tx1->balance_after);
        $this->assertEquals(10.0, $tx1->average_cost_after);

        // Shipment 2: 50 units @ $16.00
        // New WAC = ((100 * 10) + (50 * 16)) / 150 = (1000 + 800) / 150 = 1800 / 150 = $12.00
        $tx2 = $postingService->post(
            itemId: $item->id,
            warehouseId: $warehouse->id,
            type: InventoryTransactionType::IN,
            quantityBase: 50.0,
            unitCostBase: 16.0,
            referenceModel: $item,
            userId: $user->id,
            notes: 'Second Shipment'
        );

        $inventory->refresh();

        $this->assertEquals(150.0, $inventory->current_quantity);
        $this->assertEquals(12.0, $inventory->average_cost);
        $this->assertEquals(1800.0, $inventory->stock_value);
        $this->assertEquals(150.0, $tx2->balance_after);
        $this->assertEquals(12.0, $tx2->average_cost_after);
    }

    public function test_inventory_posting_service_outflow_retains_wac_and_decrements_stock(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-WAC-02',
            'name' => 'Outflow Test Item',
        ]);

        $postingService = app(InventoryPostingService::class);

        // Inflow 100 units @ $12.00
        $postingService->post($item->id, $warehouse->id, InventoryTransactionType::IN, 100.0, 12.0, $item, $user->id);

        // Outflow 30 units
        $txOut = $postingService->post(
            itemId: $item->id,
            warehouseId: $warehouse->id,
            type: InventoryTransactionType::OUT,
            quantityBase: 30.0,
            unitCostBase: 0.0, // Outflow ignores input cost; uses WAC
            referenceModel: $item,
            userId: $user->id,
            notes: 'Sale Outflow'
        );

        $inventory = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();

        $this->assertEquals(70.0, $inventory->current_quantity);
        $this->assertEquals(12.0, $inventory->average_cost); // WAC unchanged
        $this->assertEquals(840.0, $inventory->stock_value); // 70 * 12
        $this->assertEquals(12.0, $txOut->unit_cost);
        $this->assertEquals(360.0, $txOut->total_cost); // 30 * 12
    }

    public function test_negative_stock_exception_blocks_overdrawn_outflow(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-WAC-03',
            'name' => 'Negative Stock Test',
        ]);

        $postingService = app(InventoryPostingService::class);

        // Inflow 10 units
        $postingService->post($item->id, $warehouse->id, InventoryTransactionType::IN, 10.0, 5.0, $item, $user->id);

        // Attempt Outflow 15 units (exceeds 10)
        $this->expectException(InsufficientStockException::class);

        $postingService->post($item->id, $warehouse->id, InventoryTransactionType::OUT, 15.0, 5.0, $item, $user->id);
    }

    public function test_inventory_transaction_ledger_is_immutable(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'ITEM-IMMUTABLE', 'name' => 'Immutable Item']);

        $postingService = app(InventoryPostingService::class);
        $tx = $postingService->post($item->id, $warehouse->id, InventoryTransactionType::IN, 10.0, 5.0, $item, $user->id);

        $this->expectException(Exception::class);
        $tx->delete();
    }
}
