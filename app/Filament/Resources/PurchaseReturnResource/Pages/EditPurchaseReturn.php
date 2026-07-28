<?php

namespace App\Filament\Resources\PurchaseReturnResource\Pages;

use App\Actions\Purchasing\ProcessPurchaseReturnAction;
use App\Enums\ReturnStatus;
use App\Filament\Resources\PurchaseReturnResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirm & Post Purchase Return')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ReturnStatus::DRAFT)
                ->action(function (ProcessPurchaseReturnAction $action) {
                    $action->execute($this->record, auth()->id() ?? 1);

                    Notification::make()
                        ->title('Purchase Return Confirmed')
                        ->body("Purchase return #{$this->record->return_number} posted successfully.")
                        ->success()
                        ->send();

                    $this->refreshFormData();
                }),
        ];
    }
}
