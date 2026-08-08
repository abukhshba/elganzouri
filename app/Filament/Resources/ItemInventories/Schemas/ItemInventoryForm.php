<?php

namespace App\Filament\Resources\ItemInventories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemInventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.item_inventory'))
                    ->schema([
                        Select::make('warehouse_id')
                            ->label(__('erp.resources.warehouse'))
                            ->relationship('warehouse', 'name')
                            ->disabled(),
                        Select::make('item_id')
                            ->label(__('erp.resources.item'))
                            ->relationship('item', 'name')
                            ->disabled(),
                        TextInput::make('quantity_on_hand')
                            ->label(__('erp.fields.quantity_on_hand'))
                            ->disabled(),
                        TextInput::make('avg_cost')
                            ->label(__('erp.fields.avg_cost'))
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}
