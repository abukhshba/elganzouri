<?php

namespace Tests\Feature;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Enums\PurchaseStatus;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\ItemUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone5Test extends TestCase
{
    use RefreshDatabase;

    public function test_draft_purchase_does_not_affect_inventory_or_supplier_balance(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Cookware', 'slug' => 'cookware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $supplier = Supplier::create(['name' => 'Pyrex Vendor', 'balance' => 0.0]);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-PO-01',
            'name' => 'Pyrex Dish',
        ]);

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseStatus::DRAFT,
            'total_amount' => 1000.0,
            'paid_amount' => 0.0,
            'due_amount' => 1000.0,
            'issue_date' => now(),
            'user_id' => $user->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'item_id' => $item->id,
            'unit_id' => $piece->id,
            'quantity' => 10.0,
            'unit_price' => 100.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 10.0,
            'base_unit_cost' => 100.0,
            'line_total' => 1000.0,
        ]);

        // Assert zero ledger transactions created
        $this->assertEquals(0, InventoryTransaction::count());
        $this->assertEquals(0.0, $supplier->fresh()->balance);
        $this->assertEquals(PurchaseStatus::DRAFT, $purchase->status);
    }

    public function test_confirming_purchase_converts_uom_posts_stock_in_and_updates_supplier_balance(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $carton = Unit::create(['name' => 'Carton', 'short_name' => 'ctn']);
        $category = Category::create(['name' => 'Cookware', 'slug' => 'cookware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $supplier = Supplier::create(['name' => 'Pyrex Vendor', 'balance' => 0.0]);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-PO-02',
            'name' => 'Pyrex Glass Bowl',
        ]);

        // Multi-UOM: 1 Carton = 10 Pieces
        ItemUnit::create([
            'item_id' => $item->id,
            'unit_id' => $carton->id,
            'conversion_factor' => 10.0000,
        ]);

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseStatus::DRAFT,
            'total_amount' => 5000.0,
            'paid_amount' => 1000.0, // Partial payment
            'due_amount' => 4000.0,
            'issue_date' => now(),
            'user_id' => $user->id,
        ]);

        // Purchasing 5 Cartons @ $1000.00 per Carton = $5000 Total
        // Base Qty = 5 Cartons * 10 = 50 Pieces
        // Base Unit Cost = $1000 / 10 = $100 per Piece
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'item_id' => $item->id,
            'unit_id' => $carton->id,
            'quantity' => 5.0,
            'unit_price' => 1000.0,
            'conversion_factor' => 10.0,
            'base_quantity' => 50.0,
            'base_unit_cost' => 100.0,
            'line_total' => 5000.0,
        ]);

        $action = app(ProcessPurchaseInvoiceAction::class);
        $action->execute($purchase, $user->id);

        $purchase->refresh();
        $inventory = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();

        $this->assertEquals(PurchaseStatus::CONFIRMED, $purchase->status);
        $this->assertEquals(50.0, $inventory->current_quantity); // 50 Pieces in Base Unit
        $this->assertEquals(100.0, $inventory->average_cost); // WAC = $100/piece
        $this->assertEquals(5000.0, $inventory->stock_value);
        $this->assertEquals(4000.0, $supplier->fresh()->balance); // $5000 - $1000 paid = $4000 AP debt
        $this->assertEquals(1, InventoryTransaction::count());
    }

    public function test_confirming_non_draft_purchase_throws_exception(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Cookware', 'slug' => 'cookware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $supplier = Supplier::create(['name' => 'Pyrex Vendor', 'balance' => 0.0]);

        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'ITEM-PO-03', 'name' => 'Item 3']);

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseStatus::CONFIRMED,
            'total_amount' => 500.0,
            'issue_date' => now(),
            'user_id' => $user->id,
        ]);

        $this->expectException(Exception::class);

        $action = app(ProcessPurchaseInvoiceAction::class);
        $action->execute($purchase, $user->id);
    }
}
