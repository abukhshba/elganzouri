<?php

namespace App\Filament\Resources\Purchases\Pages\Concerns;

use App\Enums\PaymentStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Validation\ValidationException;

trait HandlesPurchaseFormData
{
    protected bool $isPaidInFull = false;

    /**
     * Create the inline supplier if requested, validate the paid amount and strip form-only fields.
     */
    protected function preparePurchaseData(array $data): array
    {
        if (! empty($data['is_new_supplier'])) {
            $supplier = Supplier::create([
                'name' => $data['new_supplier_name'],
                'phone' => $data['new_supplier_phone'] ?? null,
                'is_active' => true,
            ]);
            $data['supplier_id'] = $supplier->id;
        }

        $this->isPaidInFull = ! empty($data['is_paid']);

        $totalAmount = (float) ($data['total_amount'] ?? 0);
        $paidAmount = (float) ($data['paid_amount'] ?? 0);

        if (! $this->isPaidInFull && $paidAmount > $totalAmount) {
            throw ValidationException::withMessages([
                'data.paid_amount' => __('erp.validation.paid_exceeds_total'),
            ]);
        }

        unset(
            $data['is_new_supplier'], $data['new_supplier_name'], $data['new_supplier_phone'],
            $data['is_paid'], $data['status'], $data['payment_status'],
        );

        return $data;
    }

    /**
     * Recompute totals from the stored lines (never trust client-side sums) and derive payment status.
     */
    protected function syncPurchaseTotals(Purchase $purchase): void
    {
        $totalAmount = round((float) $purchase->items()->get()->sum(fn ($line) => (float) $line->line_total), 4);
        $paidAmount = $this->isPaidInFull ? $totalAmount : min((float) $purchase->paid_amount, $totalAmount);

        $purchase->update([
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'due_amount' => round($totalAmount - $paidAmount, 4),
            'payment_status' => match (true) {
                $totalAmount > 0 && $paidAmount >= $totalAmount => PaymentStatus::PAID,
                $paidAmount > 0 => PaymentStatus::PARTIAL,
                default => PaymentStatus::UNPAID,
            },
        ]);
    }
}
