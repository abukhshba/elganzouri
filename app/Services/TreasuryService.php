<?php

namespace App\Services;

use App\Enums\CashboxTransactionType;
use App\Exceptions\CashboxBalanceException;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TreasuryService
{
    /**
     * Post a cashbox transaction atomically with drawer overdraft protection.
     */
    public function postTransaction(
        int $cashboxId,
        CashboxTransactionType $type,
        float $amount,
        Model $referenceModel,
        int $userId,
        ?string $description = null
    ): CashboxTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Transaction amount must be greater than zero.");
        }

        return DB::transaction(function () use ($cashboxId, $type, $amount, $referenceModel, $userId, $description) {
            // Apply pessimistic lock on cashbox drawer
            $cashbox = Cashbox::where('id', $cashboxId)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) $cashbox->current_balance;

            if ($type === CashboxTransactionType::OUT) {
                if ($amount > $balanceBefore) {
                    throw new CashboxBalanceException(
                        "Insufficient cash drawer balance in cashbox '{$cashbox->name}'. Available: {$balanceBefore}, Requested: {$amount}."
                    );
                }
                $balanceAfter = round($balanceBefore - $amount, 4);
            } else {
                $balanceAfter = round($balanceBefore + $amount, 4);
            }

            // Create cashbox audit transaction entry
            $transaction = CashboxTransaction::create([
                'cashbox_id' => $cashboxId,
                'transaction_type' => $type,
                'reference_type' => get_class($referenceModel),
                'reference_id' => $referenceModel->getKey(),
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'user_id' => $userId,
                'description' => $description,
                'created_at' => now(),
            ]);

            // Update cashbox balance
            $cashbox->update([
                'current_balance' => $balanceAfter,
            ]);

            return $transaction;
        });
    }
}
