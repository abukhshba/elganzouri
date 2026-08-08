<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sale_creation_with_inline_new_customer(): void
    {
        $warehouse = Warehouse::first();
        $item = Item::first();
        $unit = Unit::first();

        $this->assertDatabaseMissing('customers', [
            'name' => 'العميل الجديد للاختبار',
            'phone' => '01099998888',
        ]);

        $saleData = [
            'is_new_customer' => true,
            'new_customer_name' => 'العميل الجديد للاختبار',
            'new_customer_phone' => '01099998888',
            'status' => SaleStatus::DRAFT->value,
            'is_paid' => true,
            'items' => [
                [
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'unit_id' => $unit->id,
                    'quantity' => 2,
                    'unit_price' => 100.0,
                ],
            ],
        ];

        // Simulate page creation logic
        $page = new \App\Filament\Resources\Sales\Pages\CreateSale();
        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $processedData = $method->invoke($page, $saleData);

        $sale = Sale::create($processedData);
        $sale->items()->createMany($processedData['items']);

        $page->record = $sale;
        $afterMethod = $reflection->getMethod('afterCreate');
        $afterMethod->setAccessible(true);
        $afterMethod->invoke($page);

        // Assert customer was created
        $this->assertDatabaseHas('customers', [
            'name' => 'العميل الجديد للاختبار',
            'phone' => '01099998888',
        ]);

        $customer = Customer::where('phone', '01099998888')->first();
        $this->assertEquals($customer->id, $sale->customer_id);
        $this->assertEquals(200.0, (float) $sale->total_amount);
        $this->assertEquals(PaymentStatus::PAID, $sale->payment_status);
        $this->assertEquals($warehouse->id, $sale->items->first()->warehouse_id);
    }

    public function test_sale_creation_with_full_cashbox_payment(): void
    {
        $customer = Customer::first();
        $warehouse = Warehouse::first();
        $cashbox = Cashbox::first();
        $item = Item::first();
        $unit = Unit::first();

        $initialBalance = (float) $cashbox->balance;

        $saleData = [
            'customer_id' => $customer->id,
            'cashbox_id' => $cashbox->id,
            'is_paid' => true,
            'items' => [
                [
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'unit_id' => $unit->id,
                    'quantity' => 5,
                    'unit_price' => 200.0,
                ],
            ],
        ];

        $page = new \App\Filament\Resources\Sales\Pages\CreateSale();
        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $processedData = $method->invoke($page, $saleData);

        $sale = Sale::create($processedData);
        $sale->items()->createMany($processedData['items']);

        $page->record = $sale;
        $afterMethod = $reflection->getMethod('afterCreate');
        $afterMethod->setAccessible(true);
        $afterMethod->invoke($page);

        $cashbox->refresh();
        $this->assertEquals(1000.0, (float) $sale->total_amount);
        $this->assertEquals(1000.0, (float) $sale->paid_amount);
        $this->assertEquals(PaymentStatus::PAID, $sale->payment_status);
        $this->assertEquals($initialBalance + 1000.0, (float) $cashbox->balance);

        $this->assertDatabaseHas('cashbox_transactions', [
            'cashbox_id' => $cashbox->id,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'amount' => 1000.0,
        ]);
    }

    public function test_sale_creation_with_partial_payment(): void
    {
        $customer = Customer::first();
        $warehouse = Warehouse::first();
        $cashbox = Cashbox::first();
        $item = Item::first();
        $unit = Unit::first();

        $saleData = [
            'customer_id' => $customer->id,
            'cashbox_id' => $cashbox->id,
            'is_paid' => false,
            'paid_amount' => 300.0,
            'items' => [
                [
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'unit_id' => $unit->id,
                    'quantity' => 2,
                    'unit_price' => 500.0,
                ],
            ],
        ];

        $page = new \App\Filament\Resources\Sales\Pages\CreateSale();
        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $processedData = $method->invoke($page, $saleData);

        $sale = Sale::create($processedData);
        $sale->items()->createMany($processedData['items']);

        $page->record = $sale;
        $afterMethod = $reflection->getMethod('afterCreate');
        $afterMethod->setAccessible(true);
        $afterMethod->invoke($page);

        $this->assertEquals(1000.0, (float) $sale->total_amount);
        $this->assertEquals(300.0, (float) $sale->paid_amount);
        $this->assertEquals(700.0, (float) $sale->due_amount);
        $this->assertEquals(PaymentStatus::PARTIAL, $sale->payment_status);
    }
}
