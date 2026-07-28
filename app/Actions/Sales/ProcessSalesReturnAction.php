<?php

namespace App\Actions\Sales;

use App\Enums\CashboxTransactionType;
use App\Enums\InventoryTransactionType;
use App\Enums\ReturnStatus;
use App\Models\Cashbox;
use App\Models\ItemInventory;
use App\Models\SalesReturn;
use App\Services\InventoryPostingService;
use App\Services\TreasuryService;
use App\Services\UomService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProcessSalesReturnAction
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService,
        protected TreasuryService $treasuryService,
        protected UomService $uomService
    ) {}

    /**
     * Confirm a customer sales return, post stock RETURN_IN, recalculate WAC, reverse COGS, decrement AR or refund cash.
     */
    public function execute(SalesReturn $return, int $userId): SalesReturn
    {
        return DB::transaction(function () use ($return, $userId) {
            if ($return->status !== ReturnStatus::DRAFT) {
                throw new Exception("Only DRAFT sales returns can be confirmed.");
            }

            $return->status = ReturnStatus::CONFIRMED;
            $totalCogs = 0.0;

            foreach ($return->items as $item) {
                $baseQuantity = $this->uomService->convertQuantityToBaseUnit(
                    $item->item_id,
                    $item->unit_id,
                    (float) $item->quantity
                );

                // Determine unit cost for returning goods to inventory
                // If original sale exists, pull captured cost; otherwise use current WAC
                $inventory = ItemInventory::where('item_id', $item->item_id)->where('warehouse_id', $return->warehouse_id)->first();
                $returnUnitCost = (float) ($inventory?->average_cost ?? 0.0);

                // Post RETURN_IN to warehouse (increments stock, recalculates WAC dynamically!)
                $this->inventoryPostingService->post(
                    itemId: $item->item_id,
                    warehouseId: $return->warehouse_id,
                    type: InventoryTransactionType::RETURN_IN,
                    quantityBase: $baseQuantity,
                    unitCostBase: $returnUnitCost,
                    referenceModel: $return,
                    userId: $userId,
                    notes: "Sales Return #{$return->return_number}"
                );

                $lineTotal = round((float) $item->quantity * (float) $item->unit_price, 4);
                $lineCogs = round($baseQuantity * $returnUnitCost, 4);

                $item->update([
                    'conversion_factor' => $this->uomService->getConversionFactor($item->item_id, $item->unit_id),
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => $returnUnitCost,
                    'line_total' => $lineTotal,
                    'line_cogs' => $lineCogs,
                ]);

                $totalCogs += $lineCogs;
            }

            $totalAmount = (float) $return->total_amount;
            $refundedAmount = (float) $return->refunded_amount;

            // 1. Cash refund disbursed from cashbox
            if ($refundedAmount > 0) {
                $cashboxId = $return->cashbox_id ?? Cashbox::where('is_active', true)->value('id');

                if ($cashboxId) {
                    $this->treasuryService->postTransaction(
                        cashboxId: $cashboxId,
                        type: CashboxTransactionType::OUT,
                        amount: $refundedAmount,
                        referenceModel: $return,
                        userId: $userId,
                        description: "Sales Return Cash Refund #{$return->return_number}"
                    );
                }
            }

            // 2. Decrement Customer AR Debt Balance by credit portion (total_amount - refunded_amount)
            $arReduction = round($totalAmount - $refundedAmount, 4);

            if ($arReduction > 0) {
                $return->customer->decrement('balance', $arReduction);
            }

            $return->total_cogs = round($totalCogs, 4);
            $return->save();

            return $return;
        });
    }
}
