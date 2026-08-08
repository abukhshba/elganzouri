<?php

namespace App\Filament\Resources\PurchaseReturns\Tables;

use App\Enums\ReturnStatus;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseReturnsTable
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
                Tables\Columns\TextColumn::make('purchase.purchase_number')
                    ->label(__('erp.fields.original_purchase'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('erp.resources.supplier'))
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
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label(__('erp.resources.supplier')),
                Tables\Filters\SelectFilter::make('status')
                    ->options(ReturnStatus::options())
                    ->label(__('erp.fields.status')),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }
}
