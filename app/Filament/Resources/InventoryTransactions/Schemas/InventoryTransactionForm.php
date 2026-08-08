<?php

namespace App\Filament\Resources\InventoryTransactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.inventory_transaction'))
                    ->schema([
                        Select::make('warehouse_id')
                            ->label(__('erp.resources.warehouse'))
                            ->relationship('warehouse', 'name')
                            ->disabled(),
                        Select::make('item_id')
                            ->label(__('erp.resources.item'))
                            ->relationship('item', 'name')
                            ->disabled(),
                        TextInput::make('type')
                            ->label(__('erp.fields.type'))
                            ->disabled(),
                        TextInput::make('quantity_change')
                            ->label(__('erp.fields.quantity_change'))
                            ->disabled(),
                        TextInput::make('unit_cost')
                            ->label(__('erp.fields.unit_cost'))
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}
