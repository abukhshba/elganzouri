<?php

namespace Database\Seeders;

use App\Actions\Inventory\ProcessStockAdjustmentAction;
use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentItem;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventoryAdjustmentSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first() ?? Warehouse::first();
        $user = User::first() ?? User::factory()->create();
        $item = Item::first();

        if (! $item) return;

        $unitDetail = MeasurementUnitDetail::where('measurement_unit_id', $item->measurement_unit_id)->first();
        if (! $unitDetail) return;

        // Stock Adjustment IN
        $adjIn = InventoryAdjustment::create([
            'warehouse_id' => $warehouse?->id,
            'adjustment_type' => AdjustmentType::IN,
            'reason' => AdjustmentReason::FOUND,
            'status' => 'DRAFT',
            'user_id' => $user->id,
            'notes' => 'Found extra 10 items during physical audit',
        ]);

        InventoryAdjustmentItem::create([
            'inventory_adjustment_id' => $adjIn->id,
            'item_id' => $item->id,
            'measurement_unit_detail_id' => $unitDetail->id,
            'quantity' => 10.0,
            'unit_cost' => 25.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 10.0,
            'line_total' => 250.0,
        ]);

        app(ProcessStockAdjustmentAction::class)->execute($adjIn, $user->id);
    }
}
