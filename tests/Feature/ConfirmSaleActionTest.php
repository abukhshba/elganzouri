<?php

namespace Tests\Feature;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Enums\PurchaseStatus;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\Pages\EditSale;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Filament\Resources\Sales\SalesResource;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ItemInventory;
use App\Models\Item;
use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConfirmSaleActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Warehouse $warehouse;

    protected Cashbox $cashbox;

    protected Item $item;

    protected MeasurementUnitDetail $piece;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $unitGroup = MeasurementUnit::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $this->piece = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Piece', 'unit_type' => 'numbers', 'is_default' => true, 'value' => 1]);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $this->warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $this->cashbox = Cashbox::create(['name' => 'POS Cashbox', 'current_balance' => 1000.0]);
        $supplier = Supplier::create(['name' => 'Vendor']);
        $this->item = Item::create(['category_id' => $category->id, 'measurement_unit_id' => $unitGroup->id, 'sku' => 'CUP-01', 'name' => 'Cup']);

        // Stock 100 pieces @ 10 each
        $purchase = Purchase::create(['supplier_id' => $supplier->id, 'warehouse_id' => $this->warehouse->id, 'status' => PurchaseStatus::DRAFT, 'total_amount' => 1000.0, 'issue_date' => now(), 'user_id' => $this->user->id]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'item_id' => $this->item->id, 'measurement_unit_detail_id' => $this->piece->id, 'quantity' => 100.0, 'unit_price' => 10.0, 'conversion_factor' => 1.0, 'base_quantity' => 100.0, 'base_unit_cost' => 10.0, 'line_total' => 1000.0]);
        app(ProcessPurchaseInvoiceAction::class)->execute($purchase, $this->user->id);
    }

    protected function makeDraftSale(Customer $customer, float $paid): Sale
    {
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'cashbox_id' => $this->cashbox->id,
            'status' => SaleStatus::DRAFT,
            'total_amount' => 600.0,
            'paid_amount' => $paid,
            'due_amount' => 600.0 - $paid,
            'issue_date' => now(),
            'user_id' => $this->user->id,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'measurement_unit_detail_id' => $this->piece->id,
            'quantity' => 30.0,
            'unit_price' => 20.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 30.0,
            'line_total' => 600.0,
        ]);

        return $sale;
    }

    public function test_confirm_table_action_posts_stock_cash_and_customer_balance_once(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'balance' => 0.0]);
        $sale = $this->makeDraftSale($customer, 400.0);

        Livewire::test(ListSales::class)
            ->assertActionVisible(TestAction::make('confirm')->table($sale))
            ->callAction(TestAction::make('confirm')->table($sale))
            ->assertNotified()
            ->assertActionHidden(TestAction::make('confirm')->table($sale));

        $sale->refresh();
        $this->assertEquals(SaleStatus::CONFIRMED, $sale->status);
        $this->assertEquals(70.0, (float) ItemInventory::where('item_id', $this->item->id)->value('current_quantity'));
        $this->assertEquals(1400.0, (float) $this->cashbox->fresh()->current_balance);
        $this->assertEquals(1, CashboxTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->count());
        $this->assertEquals(200.0, (float) $customer->fresh()->balance);
        $this->assertEquals(300.0, (float) $sale->total_profit);
    }

    public function test_confirm_header_action_on_edit_page(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'balance' => 0.0]);
        $sale = $this->makeDraftSale($customer, 600.0);

        Livewire::test(EditSale::class, ['record' => $sale->getRouteKey()])
            ->callAction('confirm')
            ->assertNotified()
            ->assertActionHidden('confirm');

        $this->assertEquals(SaleStatus::CONFIRMED, $sale->fresh()->status);
    }

    public function test_credit_limit_failure_shows_notification_and_keeps_draft(): void
    {
        $customer = Customer::create(['name' => 'Limited', 'credit_limit' => 500.0, 'balance' => 400.0]);
        $sale = $this->makeDraftSale($customer, 0.0);

        Livewire::test(ListSales::class)
            ->callAction(TestAction::make('confirm')->table($sale))
            ->assertNotified(__('erp.actions.confirm_sale_failed'));

        $this->assertEquals(SaleStatus::DRAFT, $sale->fresh()->status);
        $this->assertEquals(100.0, (float) ItemInventory::where('item_id', $this->item->id)->value('current_quantity'));
        $this->assertEquals(400.0, (float) $customer->fresh()->balance);
    }

    public function test_confirm_action_hidden_for_confirmed_sale(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'balance' => 0.0]);
        $sale = $this->makeDraftSale($customer, 600.0);
        $sale->update(['status' => SaleStatus::CONFIRMED]);

        Livewire::test(ListSales::class)
            ->assertActionHidden(TestAction::make('confirm')->table($sale));
    }

    public function test_edit_and_delete_hidden_for_confirmed_sale(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'balance' => 0.0]);
        $draft = $this->makeDraftSale($customer, 600.0);
        $confirmed = $this->makeDraftSale($customer, 600.0);
        $confirmed->update(['status' => SaleStatus::CONFIRMED]);

        Livewire::test(ListSales::class)
            ->assertActionVisible(TestAction::make('edit')->table($draft))
            ->assertActionHidden(TestAction::make('edit')->table($confirmed));

        Livewire::test(EditSale::class, ['record' => $draft->getRouteKey()])
            ->assertActionVisible('delete');

        $this->get(SalesResource::getUrl('edit', ['record' => $confirmed]))->assertForbidden();
    }

    public function test_delete_header_action_hidden_after_confirming_on_edit_page(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'balance' => 0.0]);
        $sale = $this->makeDraftSale($customer, 600.0);

        Livewire::test(EditSale::class, ['record' => $sale->getRouteKey()])
            ->callAction('confirm')
            ->assertActionHidden('delete');

        $this->assertModelExists($sale);
    }

    public function test_editing_a_draft_sale_saves_and_recalculates_totals(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'balance' => 0.0]);
        $sale = $this->makeDraftSale($customer, 0.0);

        Livewire::test(EditSale::class, ['record' => $sale->getRouteKey()])
            ->fillForm(['paid_amount' => 100])
            ->call('save')
            ->assertHasNoFormErrors();

        $sale->refresh();
        $this->assertEquals(600.0, (float) $sale->total_amount);
        $this->assertEquals(100.0, (float) $sale->paid_amount);
        $this->assertEquals(500.0, (float) $sale->due_amount);
        $this->assertEquals(PaymentStatus::PARTIAL, $sale->payment_status);
        $this->assertEquals(SaleStatus::DRAFT, $sale->status);
    }

    public function test_editing_a_sale_can_switch_to_a_new_customer(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'balance' => 0.0]);
        $sale = $this->makeDraftSale($customer, 0.0);

        Livewire::test(EditSale::class, ['record' => $sale->getRouteKey()])
            ->fillForm(['is_new_customer' => true, 'new_customer_name' => 'Walk-in Ali', 'new_customer_phone' => '0100'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('Walk-in Ali', $sale->fresh()->customer->name);
    }
}
