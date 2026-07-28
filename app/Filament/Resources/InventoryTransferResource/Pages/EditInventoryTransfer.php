<?php

namespace App\Filament\Resources\InventoryTransferResource\Pages;

use App\Actions\Inventory\ReceiveInventoryTransferAction;
use App\Actions\Inventory\ShipInventoryTransferAction;
use App\Enums\TransferStatus;
use App\Filament\Resources\InventoryTransferResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInventoryTransfer extends EditRecord
{
    protected static string $resource = InventoryTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('ship')
                ->label('Ship & Dispatch Outbound')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === TransferStatus::DRAFT)
                ->action(function (ShipInventoryTransferAction $action) {
                    $action->execute($this->record, auth()->id() ?? 1);

                    Notification::make()
                        ->title('Transfer Shipped')
                        ->body("Transfer #{$this->record->transfer_number} dispatched from source warehouse.")
                        ->info()
                        ->send();

                    $this->refreshFormData();
                }),

            Actions\Action::make('receive')
                ->label('Receive & Post Inbound')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === TransferStatus::SHIPPED)
                ->action(function (ReceiveInventoryTransferAction $action) {
                    $action->execute($this->record, auth()->id() ?? 1);

                    Notification::make()
                        ->title('Transfer Received')
                        ->body("Transfer #{$this->record->transfer_number} received at destination warehouse.")
                        ->success()
                        ->send();

                    $this->refreshFormData();
                }),
        ];
    }
}
