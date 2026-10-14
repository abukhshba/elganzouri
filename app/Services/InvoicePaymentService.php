<?php

namespace App\Services;

use App\Enums\CashboxTransactionType;
use App\Enums\PaymentStatus;
use App\Models\CashboxTransaction;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoicePaymentService
{
    public function __construct(
        protected TreasuryService $treasuryService
    ) {}

    /**
     * Record a follow-up payment on a confirmed invoice: cash IN for sales, cash OUT for purchases.
     * Lowers the customer/supplier balance and refreshes the invoice's paid, due and payment status.
     */
    public function addPayment(Sale|Purchase $invoice, int $cashboxId, float $amount, int $userId, ?string $description = null): CashboxTransaction
    {
        return DB::transaction(function () use ($invoice, $cashboxId, $amount, $userId, $description) {
            $locked = $invoice->newQuery()->lockForUpdate()->findOrFail($invoice->getKey());
            $amount = round($amount, 4);
            $dueAmount = round((float) $locked->due_amount, 4);

            if ($amount <= 0 || $amount > $dueAmount) {
                throw new InvalidArgumentException("Payment amount must be between 0 and the remaining {$dueAmount}.");
            }

            $isSale = $locked instanceof Sale;

            $transaction = $this->treasuryService->postTransaction(
                cashboxId: $cashboxId,
                type: $isSale ? CashboxTransactionType::IN : CashboxTransactionType::OUT,
                amount: $amount,
                referenceModel: $locked,
                userId: $userId,
                description: $description
            );

            $paidAmount = round((float) $locked->paid_amount + $amount, 4);
            $locked->update([
                'paid_amount' => $paidAmount,
                'due_amount' => round($dueAmount - $amount, 4),
                'payment_status' => self::resolveStatus((float) $locked->total_amount, $paidAmount),
            ]);

            ($isSale ? $locked->customer : $locked->supplier)->decrement('balance', $amount);

            $invoice->setRawAttributes($locked->getAttributes(), true);

            return $transaction;
        });
    }

    public static function resolveStatus(float $totalAmount, float $paidAmount): PaymentStatus
    {
        if ($totalAmount > 0 && $paidAmount >= $totalAmount) {
            return PaymentStatus::PAID;
        }

        return $paidAmount > 0 ? PaymentStatus::PARTIAL : PaymentStatus::UNPAID;
    }
}
