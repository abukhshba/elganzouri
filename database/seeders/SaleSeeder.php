<?php

namespace Database\Seeders;

use App\Actions\Sales\ProcessSaleInvoiceAction;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first() ?? Warehouse::first();
        $cashbox = Cashbox::where('is_active', true)->first();
        $user = User::first() ?? User::factory()->create();

        $items = Item::all();
        if ($items->isEmpty()) {
            return;
        }

        // Sale 1: Confirmed sale invoice (Stock OUT)
        $sale1 = Sale::create([
            'customer_id' => $customer?->id,
            'warehouse_id' => $warehouse?->id,
            'cashbox_id' => $cashbox?->id,
            'status' => SaleStatus::DRAFT,
            'payment_status' => PaymentStatus::PAID,
            'total_amount' => 900.0,
            'paid_amount' => 900.0,
            'due_amount' => 0.0,
            'issue_date' => now()->subDays(3),
            'user_id' => $user->id,
            'notes' => 'Seeded retail sales invoice',
        ]);

        $item1 = $items->first();
        $unitDetail1 = MeasurementUnitDetail::where('measurement_unit_id', $item1->measurement_unit_id)->first();

        if ($item1 && $unitDetail1) {
            SaleItem::create([
                'sale_id' => $sale1->id,
                'item_id' => $item1->id,
                'measurement_unit_detail_id' => $unitDetail1->id,
                'quantity' => 20.0,
                'unit_price' => 45.0,
                'conversion_factor' => 1.0,
                'base_quantity' => 20.0,
                'base_unit_cost' => 25.0,
                'line_total' => 900.0,
                'line_cogs' => 500.0,
                'line_profit' => 400.0,
            ]);
        }

        // Execute confirming action to post stock OUT, calculate profit, log cash receipt
        app(ProcessSaleInvoiceAction::class)->execute($sale1, $user->id);

        // Sale 2: Draft sale invoice
        $sale2 = Sale::create([
            'customer_id' => $customer?->id,
            'warehouse_id' => $warehouse?->id,
            'cashbox_id' => $cashbox?->id,
            'status' => SaleStatus::DRAFT,
            'payment_status' => PaymentStatus::UNPAID,
            'total_amount' => 850.0,
            'paid_amount' => 0.0,
            'due_amount' => 850.0,
            'issue_date' => now(),
            'user_id' => $user->id,
            'notes' => 'Seeded draft sales invoice',
        ]);

        $item2 = $items->last();
        $unitDetail2 = MeasurementUnitDetail::where('measurement_unit_id', $item2->measurement_unit_id)->first();

        if ($item2 && $unitDetail2) {
            SaleItem::create([
                'sale_id' => $sale2->id,
                'item_id' => $item2->id,
                'measurement_unit_detail_id' => $unitDetail2->id,
                'quantity' => 10.0,
                'unit_price' => 85.0,
                'conversion_factor' => 1.0,
                'base_quantity' => 10.0,
                'base_unit_cost' => 50.0,
                'line_total' => 850.0,
                'line_cogs' => 500.0,
                'line_profit' => 350.0,
            ]);
        }
    }
}
