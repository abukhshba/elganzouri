<?php

namespace App\Filament\Resources\Sales\Pages\Concerns;

use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\Sale;
use App\Services\UomConversionService;
use Illuminate\Validation\ValidationException;

trait HandlesSaleFormData
{
    /**
     * Create the inline customer if requested, derive payment fields and strip form-only fields.
     */
    protected function prepareSaleData(array $data): array
    {
        if (! empty($data['is_new_customer']) && ! empty($data['new_customer_name'])) {
            $customer = Customer::create([
                'name' => $data['new_customer_name'],
                'phone' => $data['new_customer_phone'] ?? null,
                'is_active' => true,
            ]);
            $data['customer_id'] = $customer->id;
        }

        $isPaid = ! empty($data['is_paid']);
        $totalAmount = (float) ($data['total_amount'] ?? 0);
        $paidAmount = $isPaid ? $totalAmount : (float) ($data['paid_amount'] ?? 0);

        if ($paidAmount > $totalAmount && $totalAmount > 0) {
            throw ValidationException::withMessages([
                'data.paid_amount' => 'المبلغ المدفوع لا يمكن أن يكون أكبر من إجمالي الفاتورة.',
            ]);
        }

        $data['paid_amount'] = $paidAmount;
        $data['due_amount'] = max(0, $totalAmount - $paidAmount);
        $data['payment_status'] = $this->resolvePaymentStatus($totalAmount, $paidAmount)->value;

        unset($data['is_new_customer'], $data['new_customer_name'], $data['new_customer_phone'], $data['is_paid']);

        return $data;
    }

    /**
     * Recalculate line and invoice totals from the stored items.
     */
    protected function syncSaleTotals(Sale $record): void
    {
        $conversionService = app(UomConversionService::class);

        $totalAmount = 0.0;
        $totalCogs = 0.0;

        // Recalculate computed fields on each sale item
        foreach ($record->items()->get() as $saleItem) {
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

        // Recalculate payment fields based on actual total
        $paidAmount = min((float) $record->paid_amount, $totalAmount);

        $record->update([
            'total_amount' => $totalAmount,
            'total_cogs' => $totalCogs,
            'total_profit' => $totalAmount - $totalCogs,
            'paid_amount' => $paidAmount,
            'due_amount' => max(0, $totalAmount - $paidAmount),
            'payment_status' => $this->resolvePaymentStatus($totalAmount, $paidAmount),
        ]);
    }

    protected function resolvePaymentStatus(float $totalAmount, float $paidAmount): PaymentStatus
    {
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            return PaymentStatus::PAID;
        }

        return $paidAmount > 0 ? PaymentStatus::PARTIAL : PaymentStatus::UNPAID;
    }
}
