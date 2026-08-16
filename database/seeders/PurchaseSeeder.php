<?php

namespace Database\Seeders;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Enums\PurchaseStatus;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first() ?? Warehouse::first();
        $user = User::first() ?? User::factory()->create();

        $items = Item::all();
        if ($items->isEmpty()) {
            return;
        }

        // Purchase 1: Confirmed purchase invoice (Stock IN)
        $purchase1 = Purchase::create([
            'supplier_id' => $supplier?->id,
            'warehouse_id' => $warehouse?->id,
            'status' => PurchaseStatus::DRAFT,
            'total_amount' => 5000.0,
            'paid_amount' => 5000.0,
            'due_amount' => 0.0,
            'issue_date' => now()->subDays(5),
            'user_id' => $user->id,
            'notes' => 'Seeded initial stock purchase',
        ]);

        foreach ($items as $item) {
            $unitDetail = MeasurementUnitDetail::where('measurement_unit_id', $item->measurement_unit_id)->first();
            if (! $unitDetail) continue;

            PurchaseItem::create([
                'purchase_id' => $purchase1->id,
                'item_id' => $item->id,
                'measurement_unit_detail_id' => $unitDetail->id,
                'quantity' => 100.0,
                'unit_price' => 25.0,
                'conversion_factor' => 1.0,
                'base_quantity' => 100.0,
                'base_unit_cost' => 25.0,
                'line_total' => 2500.0,
            ]);
        }

        // Execute confirming action to post stock IN & WAC
        app(ProcessPurchaseInvoiceAction::class)->execute($purchase1, $user->id);

        // Purchase 2: Draft purchase invoice
        $purchase2 = Purchase::create([
            'supplier_id' => $supplier?->id,
            'warehouse_id' => $warehouse?->id,
            'status' => PurchaseStatus::DRAFT,
            'total_amount' => 1200.0,
            'paid_amount' => 0.0,
            'due_amount' => 1200.0,
            'issue_date' => now()->subDays(1),
            'user_id' => $user->id,
            'notes' => 'Seeded draft purchase order',
        ]);

        $item2 = $items->first();
        $unitDetail2 = MeasurementUnitDetail::where('measurement_unit_id', $item2->measurement_unit_id)->first();

        if ($item2 && $unitDetail2) {
            PurchaseItem::create([
                'purchase_id' => $purchase2->id,
                'item_id' => $item2->id,
                'measurement_unit_detail_id' => $unitDetail2->id,
                'quantity' => 20.0,
                'unit_price' => 60.0,
                'conversion_factor' => 1.0,
                'base_quantity' => 20.0,
                'base_unit_cost' => 60.0,
                'line_total' => 1200.0,
            ]);
        }
    }
}
