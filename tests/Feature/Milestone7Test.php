<?php

namespace Tests\Feature;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Actions\Sales\ProcessSaleInvoiceAction;
use App\Enums\InventoryTransactionType;

use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\ItemUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone7Test extends TestCase
{
    use RefreshDatabase;

    public function test_draft_sale_does_not_affect_stock_cashbox_or_customer_balance(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Cookware', 'slug' => 'cookware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $customer = Customer::create(['name' => 'Walk-in Customer', 'balance' => 0.0]);

        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'SALE-01', 'name' => 'Pan']);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => SaleStatus::DRAFT,
            'total_amount' => 500.0,
            'paid_amount' => 500.0,
            'due_amount' => 0.0,
            'issue_date' => now(),
            'user_id' => $user->id,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'item_id' => $item->id,
            'unit_id' => $piece->id,
            'quantity' => 2.0,
            'unit_price' => 250.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 2.0,
            'line_total' => 500.0,
        ]);

        $this->assertEquals(0, InventoryTransaction::count());
        $this->assertEquals(0, CashboxTransaction::count());
        $this->assertEquals(0.0, $customer->fresh()->balance);
        $this->assertEquals(SaleStatus::DRAFT, $sale->status);
    }

    public function test_confirming_sale_decrements_stock_calculates_cogs_profit_and_logs_cash_receipt(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $pack = Unit::create(['name' => 'Pack', 'short_name' => 'pk']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $cashbox = Cashbox::create(['name' => 'POS Cashbox', 'current_balance' => 1000.0]);
        $supplier = Supplier::create(['name' => 'Vendor Pyrex']);
        $customer = Customer::create(['name' => 'Commercial Customer', 'credit_limit' => 20000.0, 'balance' => 0.0]);

        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'SALE-CUP', 'name' => 'Cup']);
        ItemUnit::create(['item_id' => $item->id, 'unit_id' => $pack->id, 'conversion_factor' => 6.0000]);

        // Step 1: Procure 100 Pieces @ $10.00 each = $1000 Total (Sets initial WAC = $10.00)
        $purchase = Purchase::create(['supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id, 'status' => PurchaseStatus::DRAFT, 'total_amount' => 1000.0, 'issue_date' => now(), 'user_id' => $user->id]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'item_id' => $item->id, 'unit_id' => $piece->id, 'quantity' => 100.0, 'unit_price' => 10.0, 'conversion_factor' => 1.0, 'base_quantity' => 100.0, 'base_unit_cost' => 10.0, 'line_total' => 1000.0]);
        app(ProcessPurchaseInvoiceAction::class)->execute($purchase, $user->id);

        // Step 2: Sell 5 Packs (5 Packs * 6 = 30 Pieces) @ $120.00 per Pack = $600.00 Revenue
        // Paid $400 cash, Due $200 on credit
        // Expected COGS = 30 Pieces * $10 WAC = $300.00
        // Expected Profit = $600 Revenue - $300 COGS = $300.00
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'cashbox_id' => $cashbox->id,
            'status' => SaleStatus::DRAFT,
            'total_amount' => 600.0,
            'paid_amount' => 400.0,
            'due_amount' => 200.0,
            'issue_date' => now(),
            'user_id' => $user->id,
        ]);

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'item_id' => $item->id,
            'unit_id' => $pack->id,
            'quantity' => 5.0,
            'unit_price' => 120.0,
            'conversion_factor' => 6.0,
            'base_quantity' => 30.0,
            'line_total' => 600.0,
        ]);

        $saleAction = app(ProcessSaleInvoiceAction::class);
        $saleAction->execute($sale, $user->id);

        $sale->refresh();
        $saleItem->refresh();
        $inventory = ItemInventory::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();

        $this->assertEquals(SaleStatus::CONFIRMED, $sale->status);
        $this->assertEquals(70.0, $inventory->current_quantity); // 100 - 30 = 70 Pieces
        $this->assertEquals(10.0, $inventory->average_cost); // WAC unchanged on sale
        $this->assertEquals(10.0, $saleItem->base_unit_cost); // Captured WAC
        $this->assertEquals(300.0, $saleItem->line_cogs); // 30 * 10
        $this->assertEquals(300.0, $saleItem->line_profit); // 600 - 300
        $this->assertEquals(300.0, $sale->total_cogs);
        $this->assertEquals(300.0, $sale->total_profit);
        $this->assertEquals(1400.0, $cashbox->fresh()->current_balance); // $1000 + $400 paid
        $this->assertEquals(200.0, $customer->fresh()->balance); // $200 AR debt
    }

    public function test_credit_limit_exceeded_throws_exception(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);

        // Customer with $500 Credit Limit and existing $400 balance
        $customer = Customer::create(['name' => 'Limit Customer', 'credit_limit' => 500.0, 'balance' => 400.0]);

        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'LIMIT-01', 'name' => 'Item']);

        // Sale adding $200 due -> Total Debt $600 > Limit $500
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => SaleStatus::DRAFT,
            'total_amount' => 200.0,
            'paid_amount' => 0.0,
            'due_amount' => 200.0,
            'issue_date' => now(),
            'user_id' => $user->id,
        ]);

        SaleItem::create(['sale_id' => $sale->id, 'item_id' => $item->id, 'unit_id' => $piece->id, 'quantity' => 1.0, 'unit_price' => 200.0, 'conversion_factor' => 1.0, 'base_quantity' => 1.0, 'line_total' => 200.0]);

        $this->expectException(CreditLimitExceededException::class);

        app(ProcessSaleInvoiceAction::class)->execute($sale, $user->id);
    }
}
