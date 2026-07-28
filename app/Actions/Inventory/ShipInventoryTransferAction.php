<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryTransactionType;
use App\Enums\TransferStatus;
use App\Models\InventoryTransfer;
use App\Services\InventoryPostingService;
use App\Services\UomService;
use Exception;
use Illuminate\Support\Facades\DB;

class ShipInventoryTransferAction
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService,
        protected UomService $uomService
    ) {}

    /**
     * Dispatch inter-warehouse transfer: posts TRANSFER_OUT at source warehouse and captures source WAC.
     */
    public function execute(InventoryTransfer $transfer, int $userId): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            if ($transfer->status !== TransferStatus::DRAFT) {
                throw new Exception("Only DRAFT transfers can be shipped.");
            }

            if ($transfer->from_warehouse_id === $to_warehouse_id = $transfer->to_warehouse_id) {
                throw new Exception("Source and destination warehouses must be different.");
            }

            $transfer->status = TransferStatus::SHIPPED;

            foreach ($transfer->items as $item) {
                $baseQuantity = $this->uomService->convertQuantityToBaseUnit(
                    $item->item_id,
                    $item->unit_id,
                    (float) $item->quantity
                );

                // Post TRANSFER_OUT from source warehouse (returns transaction with captured source WAC)
                $txOut = $this->inventoryPostingService->post(
                    itemId: $item->item_id,
                    warehouseId: $transfer->from_warehouse_id,
                    type: InventoryTransactionType::TRANSFER_OUT,
                    quantityBase: $baseQuantity,
                    unitCostBase: 0.0,
                    referenceModel: $transfer,
                    userId: $userId,
                    notes: "Warehouse Transfer #{$transfer->transfer_number} (Outbound)"
                );

                $capturedWac = (float) $txOut->unit_cost;

                $item->update([
                    'conversion_factor' => $this->uomService->getConversionFactor($item->item_id, $item->unit_id),
                    'base_quantity' => $baseQuantity,
                    'shipped_wac' => $capturedWac,
                ]);
            }

            $transfer->shipped_at = now();
            $transfer->save();

            return $transfer;
        });
    }
}
