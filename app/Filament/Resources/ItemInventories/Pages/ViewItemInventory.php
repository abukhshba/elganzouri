<?php

namespace App\Filament\Resources\ItemInventories\Pages;

use App\Filament\Resources\ItemInventories\ItemInventoryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewItemInventory extends ViewRecord
{
    protected static string $resource = ItemInventoryResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->item->name.' — '.$this->getRecord()->warehouse->name;
    }
}
