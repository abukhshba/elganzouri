<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Actions\Treasury\RecordExpenseAction;
use App\Exceptions\CashboxBalanceException;
use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    /**
     * Create the expense through RecordExpenseAction so the cash OUT is written to the cashbox ledger.
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(RecordExpenseAction::class)->execute(
                expenseCategoryId: (int) $data['expense_category_id'],
                cashboxId: (int) $data['cashbox_id'],
                amount: (float) $data['amount'],
                expenseDate: (string) $data['expense_date'],
                userId: auth()->id(),
                referenceNumber: $data['reference_number'] ?? null,
                notes: $data['notes'] ?? null,
            );
        } catch (CashboxBalanceException $e) {
            Notification::make()
                ->title(__('erp.actions.expense_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }
}
