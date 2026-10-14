<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\Actions\ConfirmPurchaseAction;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\Purchase;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchase extends ViewRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConfirmPurchaseAction::make(),
            Actions\EditAction::make()
                ->visible(fn (Purchase $record): bool => PurchaseResource::canEdit($record)),
        ];
    }
}
