<?php

namespace App\Filament\Resources\ItemInventories\Tables;

use App\Models\ItemInventory;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemInventoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['item.measurementUnit.details', 'warehouse']))
            ->columns([
                Tables\Columns\TextColumn::make('item.sku')
                    ->label(__('erp.fields.sku'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label(__('erp.resources.item'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label(__('erp.resources.warehouse'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_quantity')
                    ->label(__('erp.fields.quantity_on_hand'))
                    ->numeric(2)
                    ->sortable()
                    ->color(fn (ItemInventory $record): string => (float) $record->minimum_quantity > 0 && (float) $record->current_quantity <= (float) $record->minimum_quantity ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('base_unit')
                    ->label(__('erp.fields.base_unit'))
                    ->state(fn (ItemInventory $record): ?string => self::baseUnitName($record)),
                Tables\Columns\TextColumn::make('average_cost')
                    ->label(__('erp.fields.avg_cost'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_value')
                    ->label(__('erp.fields.stock_value'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('erp.fields.last_movement'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('item.name')
            ->filters([
                Tables\Filters\SelectFilter::make('item_id')
                    ->relationship('item', 'name')
                    ->label(__('erp.resources.item'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label(__('erp.resources.warehouse'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('low_stock')
                    ->label(__('erp.fields.low_stock'))
                    ->query(fn (Builder $query) => $query->lowStock()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->recordActions([
                Actions\ViewAction::make(),
            ]);
    }

    public static function baseUnitName(ItemInventory $record): ?string
    {
        return $record->item?->measurementUnit?->details->firstWhere('is_default', true)?->name;
    }
}
