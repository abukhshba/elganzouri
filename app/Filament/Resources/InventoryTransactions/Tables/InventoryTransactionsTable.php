<?php

namespace App\Filament\Resources\InventoryTransactions\Tables;

use App\Enums\TransactionType;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('erp.fields.timestamp'))
                    ->dateTime()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('type')
                    ->label(__('erp.fields.type'))
                    ->badge()
                    ->color(fn (TransactionType $state): string => match ($state) {
                        TransactionType::PURCHASE, TransactionType::ADJUSTMENT_IN, TransactionType::TRANSFER_IN => 'success',
                        TransactionType::SALE, TransactionType::ADJUSTMENT_OUT, TransactionType::TRANSFER_OUT => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_change')
                    ->label(__('erp.fields.quantity_change'))
                    ->numeric(4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label(__('erp.fields.unit_cost'))
                    ->money('EGP')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label(__('erp.resources.warehouse')),
                Tables\Filters\SelectFilter::make('type')
                    ->options(TransactionType::options())
                    ->label(__('erp.fields.type')),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }
}
