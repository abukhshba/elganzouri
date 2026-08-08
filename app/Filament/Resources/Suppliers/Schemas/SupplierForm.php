<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->label(__('erp.fields.name'))
                    ->required()
                    ->maxLength(150),
                Components\TextInput::make('company_name')
                    ->label(__('erp.fields.company_name'))
                    ->maxLength(150),
                Components\TextInput::make('email')
                    ->label(__('erp.fields.email'))
                    ->email()
                    ->maxLength(100),
                Components\TextInput::make('phone')
                    ->label(__('erp.fields.phone'))
                    ->tel()
                    ->maxLength(30),
                Components\TextInput::make('tax_number')
                    ->label(__('erp.fields.tax_number'))
                    ->maxLength(50),
                Components\Select::make('payment_term_id')
                    ->label(__('erp.fields.payment_term'))
                    ->relationship('paymentTerm', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Components\TextInput::make('balance')
                    ->label(__('erp.fields.ap_balance'))
                    ->numeric()
                    ->disabled(),
                Components\Toggle::make('is_active')
                    ->label(__('erp.fields.is_active'))
                    ->default(true)
                    ->required(),
                Components\Textarea::make('address')
                    ->label(__('erp.fields.address'))
                    ->columnSpanFull(),
            ]);
    }
}
