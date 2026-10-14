<?php

namespace App\Filament\Resources\OpeningBalances\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class OpeningBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opening_number')
                    ->label(__('erp.fields.code'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label(__('erp.resources.warehouse'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label(__('erp.fields.issue_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('erp.resources.items'))
                    ->counts('items'),
                Tables\Columns\TextColumn::make('total_value')
                    ->label(__('erp.fields.stock_value'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('erp.fields.user'))
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label(__('erp.resources.warehouse')),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
            ]);
    }
}
