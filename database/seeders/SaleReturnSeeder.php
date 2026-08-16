<?php

namespace Database\Seeders;

use App\Actions\Sales\ProcessSalesReturnAction;
use App\Enums\ReturnStatus;
use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class SaleReturnSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first() ?? Warehouse::first();
        $cashbox = Cashbox::where('is_active', true)->first();
        $user = User::first() ?? User::factory()->create();
        $sale = Sale::where('status', 'CONFIRMED')->first();

        $item = Item::first();
        if (! $item) return;

        $unitDetail = MeasurementUnitDetail::where('measurement_unit_id', $item->measurement_unit_id)->first();
        if (! $unitDetail) return;

        $return = SalesReturn::create([
            'sale_id' => $sale?->id,
            'customer_id' => $customer?->id,
            'warehouse_id' => $warehouse?->id,
            'cashbox_id' => $cashbox?->id,
            'status' => ReturnStatus::DRAFT,
            'total_amount' => 90.0,
            'refunded_amount' => 90.0,
            'user_id' => $user->id,
            'notes' => 'Customer return wrong size',
        ]);

        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'item_id' => $item->id,
            'measurement_unit_detail_id' => $unitDetail->id,
            'quantity' => 2.0,
            'unit_price' => 45.0,
            'unit_cost' => 25.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 2.0,
            'line_total' => 90.0,
            'line_cogs' => 50.0,
        ]);

        app(ProcessSalesReturnAction::class)->execute($return, $user->id);
    }
}
