<?php

namespace Tests\Feature;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Enums\CashboxTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Filament\RelationManagers\InvoicePaymentsRelationManager;
use App\Filament\Resources\Sales\Pages\CreateSale;
use App\Filament\Resources\Sales\Pages\ViewSale;
use App\Filament\Resources\Sales\SalesResource;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalePaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse;

    protected Cashbox $cashbox;

    protected Item $item;

    protected MeasurementUnitDetail $piece;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        $unitGroup = MeasurementUnit::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $this->piece = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Piece', 'unit_type' => 'numbers', 'is_default' => true, 'value' => 1]);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $this->warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $this->cashbox = Cashbox::create(['name' => 'Main Cashbox', 'current_balance' => 0.0]);
        $this->item = Item::create(['category_id' => $category->id, 'measurement_unit_id' => $unitGroup->id, 'sku' => 'CUP-01', 'name' => 'Cup']);
        $this->customer = Customer::create(['name' => 'Customer', 'balance' => 0.0]);

        // Stock 100 pieces @ 10 each (unpaid, so the cashbox stays at 0)
        $supplier = Supplier::create(['name' => 'Vendor']);
        $purchase = Purchase::create(['supplier_id' => $supplier->id, 'warehouse_id' => $this->warehouse->id, 'status' => PurchaseStatus::DRAFT, 'total_amount' => 1000.0, 'issue_date' => now(), 'user_id' => $user->id]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'item_id' => $this->item->id, 'measurement_unit_detail_id' => $this->piece->id, 'quantity' => 100.0, 'unit_price' => 10.0, 'conversion_factor' => 1.0, 'base_quantity' => 100.0, 'base_unit_cost' => 10.0, 'line_total' => 1000.0]);
        app(ProcessPurchaseInvoiceAction::class)->execute($purchase, $user->id);
    }

    /**
     * One line: 30 pieces x 20 = 600 EGP.
     */
    protected function createSale(array $overrides = [], int $quantity = 30): Sale
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateSale::class)
            ->fillForm(array_merge([
                'customer_id' => $this->customer->id,
                'cashbox_id' => $this->cashbox->id,
                'is_paid' => false,
                'items' => [[
                    'item_id' => $this->item->id,
                    'warehouse_id' => $this->warehouse->id,
                    'measurement_unit_detail_id' => $this->piece->id,
                    'quantity' => $quantity,
                    'unit_price' => 20,
                ]],
            ], $overrides))
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        return Sale::latest('id')->first();
    }

    protected function stock(): float
    {
        return (float) ItemInventory::where('item_id', $this->item->id)->value('current_quantity');
    }

    public function test_partially_paid_sale_is_confirmed_with_first_payment(): void
    {
        $sale = $this->createSale(['paid_amount' => 200]);

        $this->assertEquals(SaleStatus::CONFIRMED, $sale->status);
        $this->assertEquals(PaymentStatus::PARTIAL, $sale->payment_status);
        $this->assertEquals(600.0, (float) $sale->total_amount);
        $this->assertEquals(400.0, (float) $sale->due_amount);
        $this->assertEquals(70.0, $this->stock());
        $this->assertEquals(200.0, (float) $this->cashbox->fresh()->current_balance);
        $this->assertCount(1, $sale->payments);
        $this->assertEquals(CashboxTransactionType::IN, $sale->payments->first()->transaction_type);
        $this->assertEquals(400.0, (float) $this->customer->fresh()->balance);
    }

    public function test_fully_paid_sale(): void
    {
        $sale = $this->createSale(['is_paid' => true]);

        $this->assertEquals(SaleStatus::CONFIRMED, $sale->status);
        $this->assertEquals(PaymentStatus::PAID, $sale->payment_status);
        $this->assertEquals(600.0, (float) $this->cashbox->fresh()->current_balance);
        $this->assertEquals(0.0, (float) $this->customer->fresh()->balance);
    }

    public function test_insufficient_stock_keeps_sale_as_draft(): void
    {
        $sale = $this->createSale(['paid_amount' => 200], quantity: 500);

        $this->assertEquals(SaleStatus::DRAFT, $sale->status);
        $this->assertEquals(100.0, $this->stock());
        $this->assertEquals(0.0, (float) $this->cashbox->fresh()->current_balance);
        $this->assertCount(0, $sale->payments);
        $this->get(SalesResource::getUrl('view', ['record' => $sale]))->assertOk();
    }

    public function test_payments_can_be_added_until_the_sale_is_paid(): void
    {
        $sale = $this->createSale(['paid_amount' => 200]);

        $payments = fn () => Livewire::test(InvoicePaymentsRelationManager::class, [
            'ownerRecord' => $sale->fresh(),
            'pageClass' => ViewSale::class,
        ]);

        $payments()
            ->callAction(TestAction::make('addPayment')->table(), ['cashbox_id' => $this->cashbox->id, 'amount' => 401])
            ->assertHasFormErrors(['amount']);

        $payments()
            ->callAction(TestAction::make('addPayment')->table(), ['cashbox_id' => $this->cashbox->id, 'amount' => 400])
            ->assertHasNoFormErrors();

        $sale->refresh();
        $this->assertEquals(PaymentStatus::PAID, $sale->payment_status);
        $this->assertEquals(0.0, (float) $sale->due_amount);
        $this->assertCount(2, $sale->payments);
        $this->assertEquals(600.0, (float) $this->cashbox->fresh()->current_balance);
        $this->assertEquals(0.0, (float) $this->customer->fresh()->balance);

        $payments()->assertActionHidden(TestAction::make('addPayment')->table());
        $this->get(SalesResource::getUrl('view', ['record' => $sale]))->assertOk();
    }
}
