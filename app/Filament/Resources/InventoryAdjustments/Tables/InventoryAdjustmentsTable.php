<?php

namespace App\Filament\Resources\InventoryAdjustments\Tables;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('adjustment_number')
                    ->label(__('erp.fields.code'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label(__('erp.resources.warehouse'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('erp.fields.type'))
                    ->badge()
                    ->color(fn (AdjustmentType $state): string => match ($state) {
                        AdjustmentType::IN => 'success',
                        AdjustmentType::OUT => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('erp.fields.reason'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label(__('erp.resources.item'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('erp.fields.quantity'))
                    ->numeric(4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label(__('erp.fields.unit_cost'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('erp.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label(__('erp.resources.warehouse')),
                Tables\Filters\SelectFilter::make('type')
                    ->options(AdjustmentType::options())
                    ->label(__('erp.fields.type')),
                Tables\Filters\SelectFilter::make('reason')
                    ->options(AdjustmentReason::options())
                    ->label(__('erp.fields.reason')),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }
}
