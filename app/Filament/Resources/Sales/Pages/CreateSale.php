<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\CashboxTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\SalesResource;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\Sale;
use App\Services\UomConversionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateSale extends CreateRecord
{
    protected static string $resource = SalesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Inline New Customer Creation
        if (! empty($data['is_new_customer']) && ! empty($data['new_customer_name'])) {
            $customer = Customer::create([
                'name' => $data['new_customer_name'],
                'phone' => $data['new_customer_phone'] ?? null,
                'is_active' => true,
            ]);
            $data['customer_id'] = $customer->id;
        }

        unset($data['is_new_customer'], $data['new_customer_name'], $data['new_customer_phone']);

        // 2. Auto-assign Status, Document Number & Dates
        $data['status'] = SaleStatus::DRAFT->value;
        $data['issue_date'] = now();
        $data['user_id'] = auth()->id() ?? 1;

        if (empty($data['warehouse_id'])) {
            $data['warehouse_id'] = \App\Models\Warehouse::first()?->id;
        }

        // 3. Set initial totals to 0 — will be recalculated in afterCreate
        $data['total_amount'] = $data['total_amount'] ?? 0;
        $data['total_cogs'] = 0;
        $data['total_profit'] = 0;

        // 4. Payment & Paid Amount Logic
        $isPaid = ! empty($data['is_paid']);
        $totalAmount = (float) ($data['total_amount'] ?? 0);
        $paidAmount = $isPaid ? $totalAmount : (float) ($data['paid_amount'] ?? 0);

        if ($paidAmount > $totalAmount && $totalAmount > 0) {
            throw ValidationException::withMessages([
                'paid_amount' => 'المبلغ المدفوع لا يمكن أن يكون أكبر من إجمالي الفاتورة.',
            ]);
        }

        $dueAmount = max(0, $totalAmount - $paidAmount);
        $data['paid_amount'] = $paidAmount;
        $data['due_amount'] = $dueAmount;

        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $data['payment_status'] = PaymentStatus::PAID->value;
        } elseif ($paidAmount > 0) {
            $data['payment_status'] = PaymentStatus::PARTIAL->value;
        } else {
            $data['payment_status'] = PaymentStatus::UNPAID->value;
        }

        unset($data['is_paid']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Sale $record */
        $record = $this->record;
        $conversionService = app(UomConversionService::class);

        $totalAmount = 0.0;
        $totalCogs = 0.0;

        // Recalculate computed fields on each sale item
        foreach ($record->items as $saleItem) {
            $qty = (float) $saleItem->quantity;
            $price = (float) $saleItem->unit_price;
            $lineTotal = $qty * $price;

            // Compute UOM conversion
            $factor = 1.0;
            if ($saleItem->measurement_unit_detail_id) {
                $unitDetail = MeasurementUnitDetail::find($saleItem->measurement_unit_detail_id);
                if ($unitDetail) {
                    $factor = $conversionService->resolveConversionFactor($unitDetail);
                }
            }

            $baseQuantity = $qty * $factor;

            // Compute COGS
            $avgCost = 0.0;
            if ($saleItem->item_id && $saleItem->warehouse_id) {
                $itemModel = Item::find($saleItem->item_id);
                if ($itemModel) {
                    $avgCost = (float) ($itemModel->inventories()
                        ->where('warehouse_id', $saleItem->warehouse_id)
                        ->value('average_cost') ?? 0.0);
                }
            }

            $lineCogs = $baseQuantity * $avgCost;
            $lineProfit = $lineTotal - $lineCogs;

            $saleItem->update([
                'conversion_factor' => $factor,
                'base_quantity' => $baseQuantity,
                'base_unit_cost' => $avgCost,
                'line_total' => $lineTotal,
                'line_cogs' => $lineCogs,
                'line_profit' => $lineProfit,
            ]);

            $totalAmount += $lineTotal;
            $totalCogs += $lineCogs;
        }

        // Update sale totals
        $totalProfit = $totalAmount - $totalCogs;
        $paidAmount = (float) $record->paid_amount;

        // Recalculate payment fields based on actual total
        $dueAmount = max(0, $totalAmount - $paidAmount);

        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $paymentStatus = PaymentStatus::PAID;
        } elseif ($paidAmount > 0) {
            $paymentStatus = PaymentStatus::PARTIAL;
        } else {
            $paymentStatus = PaymentStatus::UNPAID;
        }

        $record->update([
            'total_amount' => $totalAmount,
            'total_cogs' => $totalCogs,
            'total_profit' => $totalProfit,
            'due_amount' => $dueAmount,
            'payment_status' => $paymentStatus,
        ]);

        // Create CashboxTransaction if paid amount > 0 and cashbox is assigned
        if ($paidAmount > 0 && $record->cashbox_id) {
            $cashbox = Cashbox::find($record->cashbox_id);

            if ($cashbox) {
                $balanceBefore = (float) $cashbox->current_balance;
                $balanceAfter = $balanceBefore + $paidAmount;

                CashboxTransaction::create([
                    'cashbox_id' => $cashbox->id,
                    'transaction_type' => CashboxTransactionType::IN,
                    'reference_type' => Sale::class,
                    'reference_id' => $record->id,
                    'amount' => $paidAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'user_id' => auth()->id() ?? 1,
                    'description' => "دفعة فاتورة مبيعات رقم #{$record->invoice_number}",
                    'created_at' => now(),
                ]);

                $cashbox->update(['current_balance' => $balanceAfter]);
            }
        }
    }
}
