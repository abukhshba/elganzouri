<?php

namespace App\Filament\Resources\SalesReturns\Tables;

use App\Enums\ReturnStatus;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class SalesReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')
                    ->label(__('erp.fields.return_number'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale.invoice_number')
                    ->label(__('erp.fields.original_sale'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('erp.resources.customer'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label(__('erp.resources.warehouse'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('erp.fields.status'))
                    ->badge()
                    ->color(fn (ReturnStatus $state): string => match ($state) {
                        ReturnStatus::CONFIRMED => 'success',
                        ReturnStatus::CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('refund_amount')
                    ->label(__('erp.fields.refund_amount'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('return_date')
                    ->label(__('erp.fields.return_date'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label(__('erp.resources.customer')),
                Tables\Filters\SelectFilter::make('status')
                    ->options(ReturnStatus::options())
                    ->label(__('erp.fields.status')),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }
}
