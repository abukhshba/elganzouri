<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemInventoryResource\Pages;
use App\Models\ItemInventory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ItemInventoryResource extends Resource
{
    protected static ?string $model = ItemInventory::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box';

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Snapshots';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema; // Read-only resource
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_quantity')
                    ->label('On-Hand Qty')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->label('Reserved Qty')
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Available Qty')
                    ->numeric(decimalPlaces: 4)
                    ->badge(),
                Tables\Columns\TextColumn::make('average_cost')
                    ->label('Unit WAC')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Stock Valuation')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('minimum_quantity')
                    ->label('Min Alert Level')
                    ->numeric(decimalPlaces: 4)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label('Warehouse'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock Alert Only')
                    ->query(fn ($query) => $query->lowStock()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only resource, no bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItemInventories::route('/'),
        ];
    }
}
