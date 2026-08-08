<?php

namespace App\Filament\Resources\ItemInventories\Pages;

use App\Filament\Resources\ItemInventories\ItemInventoryResource;
use Filament\Resources\Pages\ListRecords;

class ListItemInventories extends ListRecords
{
    protected static string $resource = ItemInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
