<?php

namespace Tests\Feature;

use App\Actions\Inventory\ProcessStockAdjustmentAction;
use App\Actions\Inventory\ReceiveInventoryTransferAction;
use App\Actions\Inventory\ShipInventoryTransferAction;
use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;

use App\Enums\InventoryTransactionType;
use App\Enums\TransferStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone8Test extends TestCase
{
    use RefreshDatabase;

    public function test_two_phase_inter_warehouse_transfer_protocol(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Cookware', 'slug' => 'cookware']);
        $wh1 = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'WH-1']);
        $wh2 = Warehouse::create(['name' => 'Branch Warehouse', 'code' => 'WH-2']);

        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'TRANSFER-ITEM-01', 'name' => 'Transfer Item']);

        $postingService = app(InventoryPostingService::class);

        // WH1 initial stock: 100 units @ $10.00
        $postingService->post($item->id, $wh1->id, InventoryTransactionType::IN, 100.0, 10.0, $item, $user->id);

        // WH2 initial stock: 20 units @ $16.00
        $postingService->post($item->id, $wh2->id, InventoryTransactionType::IN, 20.0, 16.0, $item, $user->id);

        $transfer = InventoryTransfer::create([
            'from_warehouse_id' => $wh1->id,
            'to_warehouse_id' => $wh2->id,
            'status' => TransferStatus::DRAFT,
            'user_id' => $user->id,
        ]);

        $transferItem = InventoryTransferItem::create([
            'inventory_transfer_id' => $transfer->id,
            'item_id' => $item->id,
            'unit_id' => $piece->id,
            'quantity' => 50.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 50.0,
        ]);

        // Phase 1: Ship Transfer from WH1
        $shipAction = app(ShipInventoryTransferAction::class);
        $shipAction->execute($transfer, $user->id);

        $inv1 = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $wh1->id)->first();
        $inv2 = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $wh2->id)->first();
        $transferItem->refresh();

        $this->assertEquals(TransferStatus::SHIPPED, $transfer->status);
        $this->assertEquals(50.0, $inv1->current_quantity); // WH1: 100 - 50 = 50
        $this->assertEquals(10.0, $transferItem->shipped_wac); // Captured source WAC = $10.00
        $this->assertEquals(20.0, $inv2->current_quantity); // WH2 untouched until received

        // Phase 2: Receive Transfer at WH2
        // Recalculated WH2 WAC = ((20 * 16) + (50 * 10)) / 70 = (320 + 500) / 70 = 820 / 70 = 11.7143
        $receiveAction = app(ReceiveInventoryTransferAction::class);
        $receiveAction->execute($transfer, $user->id);

        $inv2->refresh();

        $this->assertEquals(TransferStatus::RECEIVED, $transfer->status);
        $this->assertEquals(70.0, $inv2->current_quantity); // WH2: 20 + 50 = 70
        $this->assertEquals(11.7143, $inv2->average_cost); // Dynamic WH2 WAC recalculation
    }

    public function test_stock_adjustment_in_and_out_workflows(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Cookware', 'slug' => 'cookware']);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'WH-1']);
        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'ADJ-ITEM-01', 'name' => 'Adjustment Item']);

        $postingService = app(InventoryPostingService::class);
        $postingService->post($item->id, $warehouse->id, InventoryTransactionType::IN, 10.0, 50.0, $item, $user->id);

        // Adjustment IN: 5 units @ $20.00
        $adjIn = InventoryAdjustment::create([
            'warehouse_id' => $warehouse->id,
            'adjustment_type' => AdjustmentType::IN,
            'reason' => AdjustmentReason::FOUND,
            'status' => 'DRAFT',
            'user_id' => $user->id,
        ]);

        InventoryAdjustmentItem::create([
            'inventory_adjustment_id' => $adjIn->id,
            'item_id' => $item->id,
            'unit_id' => $piece->id,
            'quantity' => 5.0,
            'unit_cost' => 20.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 5.0,
        ]);

        app(ProcessStockAdjustmentAction::class)->execute($adjIn, $user->id);

        $inv = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();
        $this->assertEquals(15.0, $inv->current_quantity); // 10 + 5 = 15

        // Adjustment OUT: 20 units (Exceeds available 15 units -> Throws InsufficientStockException)
        $adjOut = InventoryAdjustment::create([
            'warehouse_id' => $warehouse->id,
            'adjustment_type' => AdjustmentType::OUT,
            'reason' => AdjustmentReason::DAMAGED,
            'status' => 'DRAFT',
            'user_id' => $user->id,
        ]);

        InventoryAdjustmentItem::create([
            'inventory_adjustment_id' => $adjOut->id,
            'item_id' => $item->id,
            'unit_id' => $piece->id,
            'quantity' => 20.0,
            'unit_cost' => 0.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 20.0,
        ]);

        $this->expectException(InsufficientStockException::class);
        app(ProcessStockAdjustmentAction::class)->execute($adjOut, $user->id);
    }
}
