<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\Actions\ConfirmPurchaseAction;
use App\Filament\Resources\Purchases\Pages\Concerns\HandlesPurchaseFormData;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\Purchase;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    use HandlesPurchaseFormData;

    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConfirmPurchaseAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (Purchase $record): bool => PurchaseResource::canDelete($record)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->preparePurchaseData($data);
    }

    protected function afterSave(): void
    {
        $this->syncPurchaseTotals($this->record);
    }
}
