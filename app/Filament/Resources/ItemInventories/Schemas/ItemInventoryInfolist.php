<?php

namespace App\Filament\Resources\ItemInventories\Schemas;

use App\Filament\Resources\ItemInventories\Tables\ItemInventoriesTable;
use App\Models\ItemInventory;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemInventoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('item.name')
                            ->label(__('erp.resources.item')),
                        TextEntry::make('item.sku')
                            ->label(__('erp.fields.sku'))
                            ->badge(),
                        TextEntry::make('warehouse.name')
                            ->label(__('erp.resources.warehouse')),
                        TextEntry::make('current_quantity')
                            ->label(__('erp.fields.quantity_on_hand'))
                            ->numeric(2)
                            ->suffix(fn (ItemInventory $record): string => ' '.ItemInventoriesTable::baseUnitName($record))
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('average_cost')
                            ->label(__('erp.fields.avg_cost'))
                            ->money('EGP'),
                        TextEntry::make('stock_value')
                            ->label(__('erp.fields.stock_value'))
                            ->money('EGP'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
