<?php

namespace App\Filament\Resources\InventoryTransfers\Pages;

use App\Filament\Resources\InventoryTransfers\InventoryTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryTransfer extends EditRecord
{
    protected static string $resource = InventoryTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
