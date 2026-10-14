<?php

namespace Tests\Feature;

use App\Enums\CashboxTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Filament\RelationManagers\InvoicePaymentsRelationManager;
use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Resources\Purchases\Pages\ViewPurchase;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseFormTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse;

    protected Cashbox $cashbox;

    protected Item $item;

    protected MeasurementUnitDetail $piece;

    protected MeasurementUnitDetail $box;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $unitGroup = MeasurementUnit::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $this->piece = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Piece', 'unit_type' => 'numbers', 'is_default' => true, 'value' => 1]);
        $this->box = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Box', 'unit_type' => 'numbers', 'is_default' => false, 'value' => 12]);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $this->warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $this->cashbox = Cashbox::create(['name' => 'Main Cashbox', 'current_balance' => 1000.0]);
        $this->item = Item::create(['category_id' => $category->id, 'measurement_unit_id' => $unitGroup->id, 'sku' => 'CUP-01', 'name' => 'Cup']);
        $this->supplier = Supplier::create(['name' => 'Existing Vendor', 'balance' => 0.0]);
    }

    /**
     * Two lines: 2 boxes x 120 + 6 pieces x 10 = 300 EGP, 30 pieces of stock.
     */
    protected function formData(array $overrides = []): array
    {
        return array_merge([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'cashbox_id' => $this->cashbox->id,
            'issue_date' => now()->toDateString(),
            'items' => [
                ['item_id' => $this->item->id, 'measurement_unit_detail_id' => $this->box->id, 'quantity' => 2, 'unit_price' => 120],
                ['item_id' => $this->item->id, 'measurement_unit_detail_id' => $this->piece->id, 'quantity' => 6, 'unit_price' => 10],
            ],
        ], $overrides);
    }

    protected function createPurchase(array $overrides = []): Purchase
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreatePurchase::class)
            ->fillForm($this->formData($overrides))
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        return Purchase::latest('id')->first();
    }

    protected function stock(): float
    {
        return (float) ItemInventory::where('item_id', $this->item->id)->value('current_quantity');
    }

    protected function cash(): float
    {
        return (float) $this->cashbox->fresh()->current_balance;
    }

    public function test_warehouse_and_cashbox_default_to_the_first_one(): void
    {
        Warehouse::create(['name' => 'Second WH', 'code' => 'WH-2']);
        Cashbox::create(['name' => 'Second Cashbox', 'current_balance' => 0]);

        Livewire::test(CreatePurchase::class)
            ->assertSchemaStateSet([
                'warehouse_id' => $this->warehouse->id,
                'cashbox_id' => $this->cashbox->id,
            ]);
    }

    public function test_line_and_invoice_totals_update_live(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreatePurchase::class)
            ->fillForm($this->formData(['paid_amount' => 100]))
            ->assertSchemaStateSet([
                'items.0.line_total' => '240.00',
                'items.1.line_total' => '60.00',
                'total_amount' => '300.00',
                'due_amount' => '200.00',
            ])
            ->fillForm(['is_paid' => true])
            ->assertSchemaStateSet(['paid_amount' => '300.00', 'due_amount' => '0.00']);

        $undoRepeaterFake();
    }

    public function test_partially_paid_purchase_is_confirmed_with_first_payment(): void
    {
        $purchase = $this->createPurchase(['paid_amount' => 100]);
        $purchase->load('items', 'payments');

        $this->assertNotEmpty($purchase->purchase_number);
        $this->assertEquals(PurchaseStatus::CONFIRMED, $purchase->status);
        $this->assertEquals(PaymentStatus::PARTIAL, $purchase->payment_status);
        $this->assertEquals(300.0, (float) $purchase->total_amount);
        $this->assertEquals(100.0, (float) $purchase->paid_amount);
        $this->assertEquals(200.0, (float) $purchase->due_amount);
        $this->assertEquals(24.0, (float) $purchase->items->first()->base_quantity); // 2 boxes x 12
        $this->assertEquals(10.0, (float) $purchase->items->first()->base_unit_cost); // 120 / 12
        $this->assertEquals(30.0, $this->stock());
        $this->assertEquals(900.0, $this->cash());
        $this->assertCount(1, $purchase->payments);
        $this->assertEquals(CashboxTransactionType::OUT, $purchase->payments->first()->transaction_type);
        $this->assertEquals(100.0, (float) $purchase->payments->first()->amount);
        $this->assertEquals(200.0, (float) $this->supplier->fresh()->balance);
    }

    public function test_fully_paid_purchase_with_new_supplier(): void
    {
        $purchase = $this->createPurchase([
            'supplier_id' => null,
            'is_new_supplier' => true,
            'new_supplier_name' => 'Brand New Vendor',
            'new_supplier_phone' => '01012345678',
            'is_paid' => true,
        ]);

        $supplier = Supplier::where('name', 'Brand New Vendor')->sole();
        $this->assertEquals('01012345678', $supplier->phone);
        $this->assertEquals($supplier->id, $purchase->supplier_id);
        $this->assertEquals(PurchaseStatus::CONFIRMED, $purchase->status);
        $this->assertEquals(PaymentStatus::PAID, $purchase->payment_status);
        $this->assertEquals(0.0, (float) $purchase->due_amount);
        $this->assertEquals(30.0, $this->stock());
        $this->assertEquals(700.0, $this->cash());
        $this->assertEquals(0.0, (float) $supplier->balance);
    }

    public function test_unpaid_purchase_is_confirmed_without_cash_movement(): void
    {
        $purchase = $this->createPurchase(['cashbox_id' => null, 'paid_amount' => 0]);

        $this->assertEquals(PurchaseStatus::CONFIRMED, $purchase->status);
        $this->assertEquals(PaymentStatus::UNPAID, $purchase->payment_status);
        $this->assertCount(0, $purchase->payments);
        $this->assertEquals(1000.0, $this->cash());
        $this->assertEquals(300.0, (float) $this->supplier->fresh()->balance);
    }

    public function test_not_enough_cash_keeps_purchase_as_draft_until_confirmed(): void
    {
        $this->cashbox->update(['current_balance' => 50]);

        $purchase = $this->createPurchase(['paid_amount' => 100]);

        $this->assertEquals(PurchaseStatus::DRAFT, $purchase->status);
        $this->assertEquals(0.0, $this->stock());
        $this->assertEquals(50.0, $this->cash());
        $this->assertEquals(0.0, (float) $this->supplier->fresh()->balance);

        // Top up the cashbox, then confirm from the view page
        $this->cashbox->update(['current_balance' => 500]);

        Livewire::test(ViewPurchase::class, ['record' => $purchase->getRouteKey()])
            ->assertActionVisible('confirm')
            ->callAction('confirm')
            ->assertNotified(__('erp.actions.confirm_purchase_success'))
            ->assertActionHidden('confirm');

        $this->assertEquals(PurchaseStatus::CONFIRMED, $purchase->fresh()->status);
        $this->assertEquals(30.0, $this->stock());
        $this->assertEquals(400.0, $this->cash());
    }

    public function test_paid_amount_cannot_exceed_total(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreatePurchase::class)
            ->fillForm($this->formData())
            ->fillForm(['paid_amount' => 500])
            ->call('create')
            ->assertHasFormErrors(['paid_amount']);

        $undoRepeaterFake();

        $this->assertEquals(0, Purchase::count());
    }

    public function test_payments_can_be_added_until_the_purchase_is_paid(): void
    {
        $purchase = $this->createPurchase(['paid_amount' => 100]);

        $payments = fn () => Livewire::test(InvoicePaymentsRelationManager::class, [
            'ownerRecord' => $purchase->fresh(),
            'pageClass' => ViewPurchase::class,
        ]);

        // More than the remaining 200 is rejected
        $payments()
            ->callAction(TestAction::make('addPayment')->table(), ['cashbox_id' => $this->cashbox->id, 'amount' => 250])
            ->assertHasFormErrors(['amount']);

        $payments()
            ->assertCanSeeTableRecords($purchase->payments)
            ->callAction(TestAction::make('addPayment')->table(), ['cashbox_id' => $this->cashbox->id, 'amount' => 150])
            ->assertHasNoFormErrors()
            ->assertNotified(__('erp.actions.add_payment_success'));

        $purchase->refresh();
        $this->assertEquals(PaymentStatus::PARTIAL, $purchase->payment_status);
        $this->assertEquals(50.0, (float) $purchase->due_amount);

        $payments()
            ->callAction(TestAction::make('addPayment')->table(), ['cashbox_id' => $this->cashbox->id, 'amount' => 50])
            ->assertHasNoFormErrors();

        $purchase->refresh();
        $this->assertEquals(PaymentStatus::PAID, $purchase->payment_status);
        $this->assertEquals(300.0, (float) $purchase->paid_amount);
        $this->assertEquals(0.0, (float) $purchase->due_amount);
        $this->assertCount(3, $purchase->payments);
        $this->assertEquals(700.0, $this->cash());
        $this->assertEquals(0.0, (float) $this->supplier->fresh()->balance);

        $payments()->assertActionHidden(TestAction::make('addPayment')->table());
    }

    public function test_payment_blocked_when_cashbox_is_short(): void
    {
        $purchase = $this->createPurchase(['paid_amount' => 0, 'cashbox_id' => null]);
        $this->cashbox->update(['current_balance' => 20]);

        Livewire::test(InvoicePaymentsRelationManager::class, ['ownerRecord' => $purchase, 'pageClass' => ViewPurchase::class])
            ->callAction(TestAction::make('addPayment')->table(), ['cashbox_id' => $this->cashbox->id, 'amount' => 100])
            ->assertNotified(__('erp.actions.add_payment_failed'));

        $purchase->refresh();
        $this->assertEquals(PaymentStatus::UNPAID, $purchase->payment_status);
        $this->assertEquals(300.0, (float) $purchase->due_amount);
        $this->assertEquals(20.0, $this->cash());
    }

    public function test_confirmed_purchase_is_viewable_but_locked(): void
    {
        $purchase = $this->createPurchase(['paid_amount' => 100]);

        Livewire::test(ListPurchases::class)
            ->assertActionHidden(TestAction::make('confirm')->table($purchase))
            ->assertActionHidden(TestAction::make('edit')->table($purchase));

        $this->get(PurchaseResource::getUrl('view', ['record' => $purchase]))->assertOk();
        $this->get(PurchaseResource::getUrl('edit', ['record' => $purchase]))->assertForbidden();
    }
}
