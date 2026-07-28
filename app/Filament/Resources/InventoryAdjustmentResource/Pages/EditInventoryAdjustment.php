<?php

namespace App\Filament\Resources\InventoryAdjustmentResource\Pages;

use App\Actions\Inventory\ProcessStockAdjustmentAction;
use App\Filament\Resources\InventoryAdjustmentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInventoryAdjustment extends EditRecord
{
    protected static string $resource = InventoryAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirm & Post Stock Adjustment')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'DRAFT')
                ->action(function (ProcessStockAdjustmentAction $action) {
                    $action->execute($this->record, auth()->id() ?? 1);

                    Notification::make()
                        ->title('Adjustment Confirmed')
                        ->body("Stock adjustment #{$this->record->adjustment_number} posted successfully.")
                        ->success()
                        ->send();

                    $this->refreshFormData();
                }),
        ];
    }
}
