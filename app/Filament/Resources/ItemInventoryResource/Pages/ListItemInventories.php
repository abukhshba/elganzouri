<?php

namespace App\Filament\Resources\ItemInventoryResource\Pages;

use App\Filament\Resources\ItemInventoryResource;
use Filament\Resources\Pages\ListRecords;

class ListItemInventories extends ListRecords
{
    protected static string $resource = ItemInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Read-only resource, no create action
        ];
    }
}
