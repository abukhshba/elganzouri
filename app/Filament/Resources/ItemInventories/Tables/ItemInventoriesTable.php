<?php

namespace App\Filament\Resources\ItemInventories\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ItemInventoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label(__('erp.resources.warehouse'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.sku')
                    ->label(__('erp.fields.sku'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label(__('erp.resources.item'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->label(__('erp.fields.quantity_on_hand'))
                    ->numeric(4)
                    ->sortable()
                    ->color(fn ($state, $record) => (float) $state <= (float) $record->item->min_stock_alert ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('item.baseUnit.short_name')
                    ->label(__('erp.fields.base_unit')),
                Tables\Columns\TextColumn::make('avg_cost')
                    ->label(__('erp.fields.avg_cost'))
                    ->money('EGP')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label(__('erp.resources.warehouse')),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }
}
