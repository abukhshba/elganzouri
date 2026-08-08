<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('expense_number')
                    ->label(__('erp.fields.code'))
                    ->default(fn () => 'EXP-'.strtoupper(bin2hex(random_bytes(4))))
                    ->required()
                    ->readOnly(),
                Components\Select::make('expense_category_id')
                    ->label(__('erp.resources.expense_category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Components\Select::make('cashbox_id')
                    ->label(__('erp.fields.disbursing_cashbox'))
                    ->relationship('cashbox', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Components\TextInput::make('amount')
                    ->label(__('erp.fields.amount'))
                    ->numeric()
                    ->prefix('EGP')
                    ->required(),
                Components\DatePicker::make('payment_date')
                    ->label(__('erp.fields.payment_date'))
                    ->default(now())
                    ->required(),
                Components\TextInput::make('reference_number')
                    ->label(__('erp.fields.reference_number'))
                    ->maxLength(100),
                Components\Textarea::make('description')
                    ->label(__('erp.fields.description'))
                    ->columnSpanFull(),
            ]);
    }
}
