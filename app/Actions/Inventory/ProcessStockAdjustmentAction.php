<?php

namespace App\Actions\Inventory;

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Models\InventoryAdjustment;
use App\Services\InventoryPostingService;
use App\Services\UomService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProcessStockAdjustmentAction
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService,
        protected UomService $uomService
    ) {}

    /**
     * Confirm a stock adjustment voucher and post ADJUSTMENT_IN or ADJUSTMENT_OUT.
     */
    public function execute(InventoryAdjustment $adjustment, int $userId): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $userId) {
            if ($adjustment->status !== 'DRAFT') {
                throw new Exception("Only DRAFT stock adjustments can be confirmed.");
            }

            $adjustment->status = 'CONFIRMED';
            $isInflow = $adjustment->adjustment_type === AdjustmentType::IN;

            foreach ($adjustment->items as $item) {
                $baseQuantity = $this->uomService->convertQuantityToBaseUnit(
                    $item->item_id,
                    $item->unit_id,
                    (float) $item->quantity
                );

                $baseUnitCost = $this->uomService->convertPriceToBaseUnit(
                    $item->item_id,
                    $item->unit_id,
                    (float) $item->unit_cost
                );

                $txType = $isInflow ? InventoryTransactionType::ADJUSTMENT_IN : InventoryTransactionType::ADJUSTMENT_OUT;

                $this->inventoryPostingService->post(
                    itemId: $item->item_id,
                    warehouseId: $adjustment->warehouse_id,
                    type: $txType,
                    quantityBase: $baseQuantity,
                    unitCostBase: $baseUnitCost,
                    referenceModel: $adjustment,
                    userId: $userId,
                    notes: "Stock Adjustment #{$adjustment->adjustment_number} ({$adjustment->reason->value})"
                );

                $item->update([
                    'conversion_factor' => $this->uomService->getConversionFactor($item->item_id, $item->unit_id),
                    'base_quantity' => $baseQuantity,
                    'line_total' => round((float) $item->quantity * (float) $item->unit_cost, 4),
                ]);
            }

            $adjustment->save();

            return $adjustment;
        });
    }
}
