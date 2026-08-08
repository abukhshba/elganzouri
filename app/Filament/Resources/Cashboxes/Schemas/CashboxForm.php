<?php

namespace App\Filament\Resources\Cashboxes\Schemas;

use Filament\Forms\Components;
use Filament\Schemas\Schema;

class CashboxForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->label(__('erp.fields.name'))
                    ->required()
                    ->maxLength(100),
                Components\Select::make('type')
                    ->label(__('erp.fields.type'))
                    ->options([
                        'CASH' => 'Physical Cash Drawer',
                        'BANK' => 'Bank Account',
                        'DIGITAL' => 'Digital Wallet / POS Gateway',
                    ])
                    ->required(),
                Components\TextInput::make('currency')
                    ->label(__('erp.fields.currency'))
                    ->default('EGP')
                    ->required()
                    ->maxLength(3),
                Components\TextInput::make('balance')
                    ->label(__('erp.fields.balance'))
                    ->numeric()
                    ->disabled(),
                Components\Toggle::make('is_active')
                    ->label(__('erp.fields.is_active'))
                    ->default(true)
                    ->required(),
            ]);
    }
}
