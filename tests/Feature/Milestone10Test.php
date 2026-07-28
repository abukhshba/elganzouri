<?php

namespace Tests\Feature;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Actions\Sales\GenerateThermalReceiptAction;
use App\Actions\Sales\ProcessSaleInvoiceAction;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone10Test extends TestCase
{
    use RefreshDatabase;

    public function test_reports_service_kpi_calculations(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $cashbox = Cashbox::create(['name' => 'Main Cashbox', 'current_balance' => 5000.0]);
        $supplier = Supplier::create(['name' => 'Supplier Co', 'balance' => 1200.0]);
        $customer = Customer::create(['name' => 'Customer Co', 'balance' => 800.0]);

        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'REPORT-ITEM-01', 'name' => 'Report Test Item']);

        // Procure stock: 100 units @ $10.00 = $1000 Total Valuation
        $purchase = Purchase::create(['supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id, 'status' => PurchaseStatus::DRAFT, 'total_amount' => 1000.0, 'issue_date' => now(), 'user_id' => $user->id]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'item_id' => $item->id, 'unit_id' => $piece->id, 'quantity' => 100.0, 'unit_price' => 10.0, 'conversion_factor' => 1.0, 'base_quantity' => 100.0, 'base_unit_cost' => 10.0, 'line_total' => 1000.0]);
        app(ProcessPurchaseInvoiceAction::class)->execute($purchase, $user->id);

        // Sell stock: 20 units @ $20.00 = $400 Total Sales, $200 COGS, $200 Profit
        $sale = Sale::create(['customer_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'cashbox_id' => $cashbox->id, 'status' => SaleStatus::DRAFT, 'total_amount' => 400.0, 'paid_amount' => 400.0, 'due_amount' => 0.0, 'issue_date' => now(), 'user_id' => $user->id]);
        SaleItem::create(['sale_id' => $sale->id, 'item_id' => $item->id, 'unit_id' => $piece->id, 'quantity' => 20.0, 'unit_price' => 20.0, 'conversion_factor' => 1.0, 'base_quantity' => 20.0, 'line_total' => 400.0]);
        app(ProcessSaleInvoiceAction::class)->execute($sale, $user->id);

        $reportsService = app(ReportsService::class);

        // 1. Inventory Valuation
        $invValuation = $reportsService->getInventoryValuationReport();
        $this->assertEquals(80.0, $invValuation['total_quantity']); // 100 - 20 = 80
        $this->assertEquals(800.0, $invValuation['total_stock_value']); // 80 * 10

        // 2. Profitability
        $profitability = $reportsService->getProfitabilityReport();
        $this->assertEquals(400.0, $profitability['total_sales']);
        $this->assertEquals(200.0, $profitability['total_cogs']);
        $this->assertEquals(200.0, $profitability['total_gross_profit']);
        $this->assertEquals(50.0, $profitability['gross_margin_percentage']);

        // 3. AR / AP Debts
        $arAp = $reportsService->getArApBalancesReport();
        $this->assertEquals(800.0, $arAp['total_customer_ar_debt']);
        $this->assertEquals(2200.0, $arAp['total_supplier_ap_debt']); // $1200 existing + $1000 purchase

        // 4. Cashbox Summary
        $cashSummary = $reportsService->getCashboxSummaryReport();
        $this->assertEquals(5400.0, $cashSummary['total_cash_in_drawers']); // $5000 + $400 sale
    }

    public function test_generate_thermal_receipt_action(): void
    {
        $user = User::factory()->create();
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $customer = Customer::create(['name' => 'Thermal Test Customer']);
        $item = Item::create(['category_id' => $category->id, 'base_unit_id' => $piece->id, 'sku' => 'RECEIPT-01', 'name' => 'Glass Cup']);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => SaleStatus::CONFIRMED,
            'total_amount' => 150.0,
            'paid_amount' => 150.0,
            'due_amount' => 0.0,
            'issue_date' => now(),
            'user_id' => $user->id,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'item_id' => $item->id,
            'unit_id' => $piece->id,
            'quantity' => 3.0,
            'unit_price' => 50.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 3.0,
            'line_total' => 150.0,
        ]);

        $action = app(GenerateThermalReceiptAction::class);
        $receipt = $action->execute($sale);

        $this->assertStringContainsString("SAL-", $receipt);
        $this->assertStringContainsString("Thermal Test Customer", $receipt);
        $this->assertStringContainsString("Glass Cup", $receipt);
        $this->assertStringContainsString("150.00 EGP", $receipt);
    }
}
