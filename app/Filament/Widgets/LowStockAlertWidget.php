<?php

namespace App\Filament\Widgets;

use App\Models\ItemInventory;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(ItemInventory::query()->lowStock())
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->badge()
                    ->label('Warehouse'),
                Tables\Columns\TextColumn::make('item.sku')
                    ->label('SKU'),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item Name'),
                Tables\Columns\TextColumn::make('current_quantity')
                    ->label('On-Hand Qty')
                    ->numeric(decimalPlaces: 4),
                Tables\Columns\TextColumn::make('minimum_quantity')
                    ->label('Min Alert Level')
                    ->numeric(decimalPlaces: 4),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Available Qty')
                    ->badge()
                    ->color('danger'),
            ]);
    }
}
