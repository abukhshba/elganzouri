<?php

namespace Database\Seeders;

use App\Actions\Purchasing\ProcessPurchaseReturnAction;
use App\Enums\ReturnStatus;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PurchaseReturnSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first() ?? Warehouse::first();
        $user = User::first() ?? User::factory()->create();
        $purchase = Purchase::where('status', 'CONFIRMED')->first();

        $item = Item::first();
        if (! $item) return;

        $unitDetail = MeasurementUnitDetail::where('measurement_unit_id', $item->measurement_unit_id)->first();
        if (! $unitDetail) return;

        $return = PurchaseReturn::create([
            'purchase_id' => $purchase?->id,
            'supplier_id' => $supplier?->id,
            'warehouse_id' => $warehouse?->id,
            'status' => ReturnStatus::DRAFT,
            'total_amount' => 125.0,
            'refunded_amount' => 125.0,
            'user_id' => $user->id,
            'notes' => 'Defective batch return',
        ]);

        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'item_id' => $item->id,
            'measurement_unit_detail_id' => $unitDetail->id,
            'quantity' => 5.0,
            'unit_price' => 25.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 5.0,
            'line_total' => 125.0,
        ]);

        app(ProcessPurchaseReturnAction::class)->execute($return, $user->id);
    }
}
