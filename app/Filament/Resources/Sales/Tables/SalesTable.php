<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('erp.fields.invoice_number'))
                    ->badge()
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
                    ->color(fn (SaleStatus $state): string => match ($state) {
                        SaleStatus::CONFIRMED => 'success',
                        SaleStatus::CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('erp.fields.payment_status'))
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::PAID => 'success',
                        PaymentStatus::PARTIAL => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('erp.fields.total_amount'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_profit')
                    ->label(__('erp.fields.total_profit'))
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label(__('erp.fields.issue_date'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label(__('erp.resources.customer')),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label(__('erp.resources.warehouse')),
                Tables\Filters\SelectFilter::make('status')
                    ->options(SaleStatus::options())
                    ->label(__('erp.fields.status')),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ]);
    }
}
