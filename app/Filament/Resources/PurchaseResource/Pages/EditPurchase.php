<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Enums\PurchaseStatus;
use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirm & Post Stock IN')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === PurchaseStatus::DRAFT)
                ->action(function (ProcessPurchaseInvoiceAction $action) {
                    $action->execute($this->record, auth()->id() ?? 1);

                    Notification::make()
                        ->title('Purchase Confirmed')
                        ->body("Purchase invoice #{$this->record->purchase_number} confirmed and posted to inventory.")
                        ->success()
                        ->send();

                    $this->refreshFormData();
                }),
        ];
    }
}
