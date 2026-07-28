<?php

namespace App\Actions\Treasury;

use App\Enums\CashboxTransactionType;
use App\Models\Expense;
use App\Services\TreasuryService;
use Illuminate\Support\Facades\DB;

class RecordExpenseAction
{
    public function __construct(
        protected TreasuryService $treasuryService
    ) {}

    /**
     * Create an operating expense voucher and post cashbox outflow.
     */
    public function execute(
        int $expenseCategoryId,
        int $cashboxId,
        float $amount,
        string $expenseDate,
        int $userId,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): Expense {
        return DB::transaction(function () use ($expenseCategoryId, $cashboxId, $amount, $expenseDate, $userId, $referenceNumber, $notes) {
            $expense = Expense::create([
                'expense_category_id' => $expenseCategoryId,
                'cashbox_id' => $cashboxId,
                'amount' => $amount,
                'expense_date' => $expenseDate,
                'user_id' => $userId,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
            ]);

            // Post cashbox outflow
            $this->treasuryService->postTransaction(
                cashboxId: $cashboxId,
                type: CashboxTransactionType::OUT,
                amount: $amount,
                referenceModel: $expense,
                userId: $userId,
                description: "Operating Expense #{$expense->expense_number}"
            );

            return $expense;
        });
    }
}
