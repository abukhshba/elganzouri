<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryTransactionType;
use App\Enums\TransferStatus;
use App\Models\InventoryTransfer;
use App\Services\InventoryPostingService;
use Exception;
use Illuminate\Support\Facades\DB;

class ReceiveInventoryTransferAction
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService
    ) {}

    /**
     * Receive inter-warehouse transfer: posts TRANSFER_IN at destination warehouse with source WAC.
     */
    public function execute(InventoryTransfer $transfer, int $userId): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            if ($transfer->status !== TransferStatus::SHIPPED) {
                throw new Exception("Only SHIPPED transfers can be received.");
            }

            $transfer->status = TransferStatus::RECEIVED;

            foreach ($transfer->items as $item) {
                $baseQuantity = (float) $item->base_quantity;
                $shippedWac = (float) $item->shipped_wac;

                // Post TRANSFER_IN at destination warehouse (recalculates destination WAC dynamically!)
                $this->inventoryPostingService->post(
                    itemId: $item->item_id,
                    warehouseId: $transfer->to_warehouse_id,
                    type: InventoryTransactionType::TRANSFER_IN,
                    quantityBase: $baseQuantity,
                    unitCostBase: $shippedWac,
                    referenceModel: $transfer,
                    userId: $userId,
                    notes: "Warehouse Transfer #{$transfer->transfer_number} (Inbound)"
                );
            }

            $transfer->received_at = now();
            $transfer->save();

            return $transfer;
        });
    }
}
