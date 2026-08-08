<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Models\Cashbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        $cashbox = Cashbox::lockForUpdate()->findOrFail($data['cashbox_id']);

        if ((float) $cashbox->balance < (float) $data['amount']) {
            Notification::make()
                ->title('Insufficient Cashbox Balance')
                ->body("Cashbox {$cashbox->name} current balance ({$cashbox->balance} EGP) is below expense amount ({$data['amount']} EGP).")
                ->danger()
                ->send();

            $this->halt();
        }

        $cashbox->decrement('balance', $data['amount']);

        return $data;
    }
}
