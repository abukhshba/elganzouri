<?php

namespace Tests\Feature;

use App\Actions\Purchasing\ProcessPurchaseReturnAction;
use App\Actions\Sales\ProcessSalesReturnAction;
use App\Enums\InventoryTransactionType;
use App\Enums\ReturnStatus;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone9Test extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_return_decrements_stock_and_reduces_supplier_ap_debt(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $cashbox = Cashbox::create(['name' => 'Main Cashbox', 'current_balance' => 1000.0]);
        $supplier = Supplier::create(['name' => 'Pyrex Supplier', 'balance' => 500.0]);

        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'PR-ITEM-01', 'name' => 'Pyrex Dish']);

        // Stock initial: 100 units @ $10.00
        $postingService = app(InventoryPostingService::class);
        $postingService->post($item->id, $warehouse->id, InventoryTransactionType::IN, 100.0, 10.0, $item, $user->id);

        // Return 20 units @ $10.00 = $200.00 total. $50 cash refund, $150 AP reduction.
        $return = PurchaseReturn::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => ReturnStatus::DRAFT,
            'total_amount' => 200.0,
            'refunded_amount' => 50.0,
            'user_id' => $user->id,
        ]);

        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'item_id' => $item->id,
            'unit_id' => $piece->id,
            'quantity' => 20.0,
            'unit_price' => 10.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 20.0,
            'line_total' => 200.0,
        ]);

        app(ProcessPurchaseReturnAction::class)->execute($return, $user->id);

        $inv = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();

        $this->assertEquals(ReturnStatus::CONFIRMED, $return->fresh()->status);
        $this->assertEquals(80.0, $inv->current_quantity); // 100 - 20 = 80
        $this->assertEquals(10.0, $inv->average_cost); // WAC unchanged
        $this->assertEquals(350.0, $supplier->fresh()->balance); // $500 - $150 = $350 AP debt
        $this->assertEquals(1050.0, $cashbox->fresh()->current_balance); // $1000 + $50 cash refund
    }

    public function test_sales_return_increments_stock_recalculates_wac_and_reduces_customer_ar_debt(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $cashbox = Cashbox::create(['name' => 'POS Cashbox', 'current_balance' => 1000.0]);
        $customer = Customer::create(['name' => 'Ahram Client', 'balance' => 400.0]);

        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'SR-ITEM-01', 'name' => 'Pyrex Bowl']);

        // Stock initial: 50 units @ $10.00
        $postingService = app(InventoryPostingService::class);
        $postingService->post($item->id, $warehouse->id, InventoryTransactionType::IN, 50.0, 10.0, $item, $user->id);

        // Sales Return: 10 units @ $25.00 selling price = $250.00 total. $100 cash refund, $150 AR reduction.
        $return = SalesReturn::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'cashbox_id' => $cashbox->id,
            'status' => ReturnStatus::DRAFT,
            'total_amount' => 250.0,
            'refunded_amount' => 100.0,
            'user_id' => $user->id,
        ]);

        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'item_id' => $item->id,
            'unit_id' => $piece->id,
            'quantity' => 10.0,
            'unit_price' => 25.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 10.0,
            'line_total' => 250.0,
        ]);

        app(ProcessSalesReturnAction::class)->execute($return, $user->id);

        $inv = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();

        $this->assertEquals(ReturnStatus::CONFIRMED, $return->fresh()->status);
        $this->assertEquals(60.0, $inv->current_quantity); // 50 + 10 = 60
        $this->assertEquals(250.0, $customer->fresh()->balance); // $400 - $150 = $250 AR debt
        $this->assertEquals(900.0, $cashbox->fresh()->current_balance); // $1000 - $100 cash refund
    }
}
