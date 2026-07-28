<?php

namespace App\Actions\Purchasing;

use App\Enums\CashboxTransactionType;
use App\Enums\InventoryTransactionType;
use App\Enums\ReturnStatus;
use App\Models\Cashbox;
use App\Models\PurchaseReturn;
use App\Services\InventoryPostingService;
use App\Services\TreasuryService;
use App\Services\UomService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProcessPurchaseReturnAction
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService,
        protected TreasuryService $treasuryService,
        protected UomService $uomService
    ) {}

    /**
     * Confirm a purchase return, post stock RETURN_OUT, decrement supplier AP debt or receive cash refund.
     */
    public function execute(PurchaseReturn $return, int $userId): PurchaseReturn
    {
        return DB::transaction(function () use ($return, $userId) {
            if ($return->status !== ReturnStatus::DRAFT) {
                throw new Exception("Only DRAFT purchase returns can be confirmed.");
            }

            $return->status = ReturnStatus::CONFIRMED;

            foreach ($return->items as $item) {
                $baseQuantity = $this->uomService->convertQuantityToBaseUnit(
                    $item->item_id,
                    $item->unit_id,
                    (float) $item->quantity
                );

                // Post RETURN_OUT from warehouse (decrements stock, retains WAC)
                $this->inventoryPostingService->post(
                    itemId: $item->item_id,
                    warehouseId: $return->warehouse_id,
                    type: InventoryTransactionType::RETURN_OUT,
                    quantityBase: $baseQuantity,
                    unitCostBase: 0.0,
                    referenceModel: $return,
                    userId: $userId,
                    notes: "Purchase Return #{$return->return_number}"
                );

                $item->update([
                    'conversion_factor' => $this->uomService->getConversionFactor($item->item_id, $item->unit_id),
                    'base_quantity' => $baseQuantity,
                    'line_total' => round((float) $item->quantity * (float) $item->unit_price, 4),
                ]);
            }

            $totalAmount = (float) $return->total_amount;
            $refundedAmount = (float) $return->refunded_amount;

            // 1. Cash refund received into cashbox
            if ($refundedAmount > 0) {
                $cashboxId = Cashbox::where('is_active', true)->value('id');

                if ($cashboxId) {
                    $this->treasuryService->postTransaction(
                        cashboxId: $cashboxId,
                        type: CashboxTransactionType::IN,
                        amount: $refundedAmount,
                        referenceModel: $return,
                        userId: $userId,
                        description: "Purchase Return Refund #{$return->return_number}"
                    );
                }
            }

            // 2. Decrement Supplier AP Debt Balance by credit portion (total_amount - refunded_amount)
            $apReduction = round($totalAmount - $refundedAmount, 4);

            if ($apReduction > 0) {
                $return->supplier->decrement('balance', $apReduction);
            }

            $return->save();

            return $return;
        });
    }
}
