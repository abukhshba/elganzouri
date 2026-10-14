<?php

namespace App\Actions\Purchasing;

use App\Enums\CashboxTransactionType;
use App\Enums\InventoryTransactionType;
use App\Enums\PurchaseStatus;
use App\Models\Cashbox;
use App\Models\Purchase;
use App\Services\InventoryPostingService;
use App\Services\TreasuryService;
use App\Services\UomService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProcessPurchaseInvoiceAction
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService,
        protected TreasuryService $treasuryService,
        protected UomService $uomService
    ) {}

    /**
     * Confirm a draft purchase invoice, post stock IN via InventoryPostingService, pay the supplier from the cashbox and update supplier AP balance.
     */
    public function execute(Purchase $purchase, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $userId) {
            if ($purchase->status !== PurchaseStatus::DRAFT) {
                throw new Exception("Only DRAFT purchase invoices can be confirmed.");
            }

            // 1. Mark status as CONFIRMED
            $purchase->status = PurchaseStatus::CONFIRMED;

            // 2. Iterate line items and post stock IN for each item
            foreach ($purchase->items as $purchaseItem) {
                $baseQuantity = $this->uomService->convertQuantityToBaseUnit(
                    $purchaseItem->measurement_unit_detail_id,
                    (float) $purchaseItem->quantity
                );

                $baseUnitCost = $this->uomService->convertPriceToBaseUnit(
                    $purchaseItem->measurement_unit_detail_id,
                    (float) $purchaseItem->unit_price
                );

                // Update purchase line item base calculations
                $purchaseItem->update([
                    'conversion_factor' => $this->uomService->getConversionFactor($purchaseItem->measurement_unit_detail_id),
                    'base_quantity' => $baseQuantity,
                    'base_unit_cost' => $baseUnitCost,
                    'line_total' => round((float) $purchaseItem->quantity * (float) $purchaseItem->unit_price, 4),
                ]);

                // Post Inventory Transaction IN
                $this->inventoryPostingService->post(
                    itemId: $purchaseItem->item_id,
                    warehouseId: $purchase->warehouse_id,
                    type: InventoryTransactionType::IN,
                    quantityBase: $baseQuantity,
                    unitCostBase: $baseUnitCost,
                    referenceModel: $purchase,
                    userId: $userId,
                    notes: "Purchase Invoice #{$purchase->purchase_number}"
                );
            }

            // 3. Record Cash Payment out of the Cashbox if paid_amount > 0
            if ((float) $purchase->paid_amount > 0) {
                $cashboxId = $purchase->cashbox_id ?? Cashbox::where('is_active', true)->value('id');

                if ($cashboxId) {
                    $this->treasuryService->postTransaction(
                        cashboxId: $cashboxId,
                        type: CashboxTransactionType::OUT,
                        amount: (float) $purchase->paid_amount,
                        referenceModel: $purchase,
                        userId: $userId,
                        description: "Purchase Payment #{$purchase->purchase_number}"
                    );
                }
            }

            // 4. Update Supplier AP Debt Balance
            $dueAmount = round((float) $purchase->total_amount - (float) $purchase->paid_amount, 4);
            $purchase->due_amount = $dueAmount;

            if ($dueAmount > 0) {
                $purchase->supplier->increment('balance', $dueAmount);
            }

            $purchase->save();

            return $purchase;
        });
    }
}
