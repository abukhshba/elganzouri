<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Actions\Treasury\RecordExpenseAction;
use App\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $action = app(RecordExpenseAction::class);

        return $action->execute(
            expenseCategoryId: (int) $data['expense_category_id'],
            cashboxId: (int) $data['cashbox_id'],
            amount: (float) $data['amount'],
            expenseDate: (string) $data['expense_date'],
            userId: auth()->id() ?? 1,
            referenceNumber: $data['reference_number'] ?? null,
            notes: $data['notes'] ?? null
        );
    }
}
