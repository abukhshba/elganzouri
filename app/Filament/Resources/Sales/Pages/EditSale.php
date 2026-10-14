<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\Actions\ConfirmSaleAction;
use App\Filament\Resources\Sales\Pages\Concerns\HandlesSaleFormData;
use App\Filament\Resources\Sales\SalesResource;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    use HandlesSaleFormData;

    protected static string $resource = SalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConfirmSaleAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (Sale $record): bool => SalesResource::canDelete($record)),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareSaleData($data);
    }

    protected function afterSave(): void
    {
        $this->syncSaleTotals($this->record);
    }
}
