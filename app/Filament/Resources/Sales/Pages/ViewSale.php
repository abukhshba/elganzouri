<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\Actions\ConfirmSaleAction;
use App\Filament\Resources\Sales\SalesResource;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConfirmSaleAction::make(),
            Actions\EditAction::make()
                ->visible(fn (Sale $record): bool => SalesResource::canEdit($record)),
        ];
    }
}
