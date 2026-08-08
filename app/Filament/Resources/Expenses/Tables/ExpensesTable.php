<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_number')
                    ->label(__('erp.fields.code'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('erp.resources.expense_category'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cashbox.name')
                    ->label(__('erp.resources.cashbox'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('erp.fields.amount'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('erp.fields.payment_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label(__('erp.fields.reference_number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->relationship('category', 'name')
                    ->label(__('erp.resources.expense_category')),
                Tables\Filters\SelectFilter::make('cashbox_id')
                    ->relationship('cashbox', 'name')
                    ->label(__('erp.resources.cashbox')),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }
}
