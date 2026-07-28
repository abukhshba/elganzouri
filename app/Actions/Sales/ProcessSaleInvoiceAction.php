<?php

namespace App\Actions\Sales;

use App\Enums\CashboxTransactionType;
use App\Enums\InventoryTransactionType;
use App\Enums\SaleStatus;
use App\Exceptions\CreditLimitExceededException;
use App\Models\Cashbox;
use App\Models\Sale;
use App\Services\InventoryPostingService;
use App\Services\TreasuryService;
use App\Services\UomService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProcessSaleInvoiceAction
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService,
        protected TreasuryService $treasuryService,
        protected UomService $uomService
    ) {}

    /**
     * Confirm a draft sales invoice, post stock OUT via InventoryPostingService, compute real-time profit, and update AR/cashbox.
     */
    public function execute(Sale $sale, int $userId): Sale
    {
        return DB::transaction(function () use ($sale, $userId) {
            if ($sale->status !== SaleStatus::DRAFT) {
                throw new Exception("Only DRAFT sales invoices can be confirmed.");
            }

            $dueAmount = round((float) $sale->total_amount - (float) $sale->paid_amount, 4);

            // 1. Credit Limit Validation
            if ($dueAmount > 0 && (float) $sale->customer->credit_limit > 0) {
                $newCustomerBalance = (float) $sale->customer->balance + $dueAmount;
                if ($newCustomerBalance > (float) $sale->customer->credit_limit) {
                    throw new CreditLimitExceededException(
                        "Customer '{$sale->customer->name}' credit limit exceeded. Limit: {$sale->customer->credit_limit}, Project Debt: {$newCustomerBalance}."
                    );
                }
            }

            // 2. Mark status as CONFIRMED
            $sale->status = SaleStatus::CONFIRMED;

            $totalCogs = 0.0;
            $totalProfit = 0.0;

            // 3. Iterate line items and post stock OUT for each item
            foreach ($sale->items as $saleItem) {
                $baseQuantity = $this->uomService->convertQuantityToBaseUnit(
                    $saleItem->item_id,
                    $saleItem->unit_id,
                    (float) $saleItem->quantity
                );

                // Post Inventory Transaction OUT (returns transaction with captured WAC)
                $txOut = $this->inventoryPostingService->post(
                    itemId: $saleItem->item_id,
                    warehouseId: $sale->warehouse_id,
                    type: InventoryTransactionType::OUT,
                    quantityBase: $baseQuantity,
                    unitCostBase: 0.0, // Outflow consumes at current WAC
                    referenceModel: $sale,
                    userId: $userId,
                    notes: "Sales Invoice #{$sale->invoice_number}"
                );

                $capturedWac = (float) $txOut->unit_cost; // Captured WAC per base unit at time of sale
                $lineTotal = round((float) $saleItem->quantity * (float) $saleItem->unit_price, 4);
                $lineCogs = round($baseQuantity * $capturedWac, 4);
                $lineProfit = round($lineTotal - $lineCogs, 4);

                $saleItem->update([
                    'conversion_factor' => $this->uomService->getConversionFactor($saleItem->item_id, $saleItem->unit_id),
                    'base_quantity' => $baseQuantity,
                    'base_unit_cost' => $capturedWac,
                    'line_total' => $lineTotal,
                    'line_cogs' => $lineCogs,
                    'line_profit' => $lineProfit,
                ]);

                $totalCogs += $lineCogs;
                $totalProfit += $lineProfit;
            }

            // 4. Record Cash Receipt in Cashbox if paid_amount > 0
            if ((float) $sale->paid_amount > 0) {
                $cashboxId = $sale->cashbox_id ?? Cashbox::where('is_active', true)->value('id');

                if ($cashboxId) {
                    $this->treasuryService->postTransaction(
                        cashboxId: $cashboxId,
                        type: CashboxTransactionType::IN,
                        amount: (float) $sale->paid_amount,
                        referenceModel: $sale,
                        userId: $userId,
                        description: "Sales Receipt #{$sale->invoice_number}"
                    );
                }
            }

            // 5. Update Customer AR Debt Balance if due_amount > 0
            $sale->due_amount = $dueAmount;

            if ($dueAmount > 0) {
                $sale->customer->increment('balance', $dueAmount);
            }

            $sale->total_cogs = round($totalCogs, 4);
            $sale->total_profit = round($totalProfit, 4);
            $sale->save();

            return $sale;
        });
    }
}
