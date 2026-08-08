<?php

namespace App\Filament\Resources\InventoryTransfers\Tables;

use App\Enums\TransferStatus;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')
                    ->label(__('erp.fields.code'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromWarehouse.name')
                    ->label(__('erp.fields.source_warehouse'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('toWarehouse.name')
                    ->label(__('erp.fields.target_warehouse'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('erp.fields.status'))
                    ->badge()
                    ->color(fn (TransferStatus $state): string => match ($state) {
                        TransferStatus::COMPLETED => 'success',
                        TransferStatus::IN_TRANSIT => 'warning',
                        TransferStatus::CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label(__('erp.fields.unique_skus')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('erp.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('from_warehouse_id')
                    ->relationship('fromWarehouse', 'name')
                    ->label(__('erp.fields.source_warehouse')),
                Tables\Filters\SelectFilter::make('to_warehouse_id')
                    ->relationship('toWarehouse', 'name')
                    ->label(__('erp.fields.target_warehouse')),
                Tables\Filters\SelectFilter::make('status')
                    ->options(TransferStatus::options())
                    ->label(__('erp.fields.status')),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }
}
