<?php

namespace Database\Seeders;

use App\Actions\Inventory\ReceiveInventoryTransferAction;
use App\Actions\Inventory\ShipInventoryTransferAction;
use App\Enums\TransferStatus;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventoryTransferSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::all();
        if ($warehouses->count() < 2) return;

        $fromWarehouse = $warehouses->first();
        $toWarehouse = $warehouses->skip(1)->first();
        $user = User::first() ?? User::factory()->create();
        $item = Item::first();

        if (! $item) return;

        $unitDetail = MeasurementUnitDetail::where('measurement_unit_id', $item->measurement_unit_id)->first();
        if (! $unitDetail) return;

        $transfer = InventoryTransfer::create([
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'status' => TransferStatus::DRAFT,
            'user_id' => $user->id,
            'notes' => 'Stock rebalancing between main and secondary warehouse',
        ]);

        InventoryTransferItem::create([
            'inventory_transfer_id' => $transfer->id,
            'item_id' => $item->id,
            'measurement_unit_detail_id' => $unitDetail->id,
            'quantity' => 15.0,
            'conversion_factor' => 1.0,
            'base_quantity' => 15.0,
        ]);

        // Ship from source warehouse
        app(ShipInventoryTransferAction::class)->execute($transfer, $user->id);

        // Receive at destination warehouse
        app(ReceiveInventoryTransferAction::class)->execute($transfer, $user->id);
    }
}
