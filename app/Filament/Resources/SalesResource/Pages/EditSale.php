<?php

namespace App\Filament\Resources\SalesResource\Pages;

use App\Actions\Sales\ProcessSaleInvoiceAction;
use App\Enums\SaleStatus;
use App\Filament\Resources\SalesResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    protected static string $resource = SalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirm & Post Sale')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === SaleStatus::DRAFT)
                ->action(function (ProcessSaleInvoiceAction $action) {
                    $action->execute($this->record, auth()->id() ?? 1);

                    Notification::make()
                        ->title('Sale Confirmed')
                        ->body("Sales invoice #{$this->record->invoice_number} confirmed, stock updated, and profit calculated.")
                        ->success()
                        ->send();

                    $this->refreshFormData();
                }),
        ];
    }
}
