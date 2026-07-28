<?php

namespace App\Filament\Resources\SalesReturnResource\Pages;

use App\Actions\Sales\ProcessSalesReturnAction;
use App\Enums\ReturnStatus;
use App\Filament\Resources\SalesReturnResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSalesReturn extends EditRecord
{
    protected static string $resource = SalesReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirm & Post Sales Return')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ReturnStatus::DRAFT)
                ->action(function (ProcessSalesReturnAction $action) {
                    $action->execute($this->record, auth()->id() ?? 1);

                    Notification::make()
                        ->title('Sales Return Confirmed')
                        ->body("Sales return #{$this->record->return_number} posted successfully.")
                        ->success()
                        ->send();

                    $this->refreshFormData();
                }),
        ];
    }
}
