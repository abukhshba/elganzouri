<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name.ar')
                    ->label(__('erp.fields.name'))
                    ->visible(fn (): bool => app()->getLocale() === 'ar')
                    ->required()
                    ->maxLength(100),
                TextInput::make('name.en')
                    ->label(__('erp.fields.name'))
                    ->visible(fn (): bool => app()->getLocale() === 'en')
                    ->required()
                    ->maxLength(100),
                TextInput::make('code')
                    ->label(__('erp.fields.code'))
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label(__('erp.fields.phone'))
                    ->tel()
                    ->maxLength(30),
                Toggle::make('is_active')
                    ->label(__('erp.fields.is_active'))
                    ->default(true)
                    ->required(),
                Textarea::make('address')
                    ->label(__('erp.fields.address'))
                    ->columnSpanFull(),
            ]);
    }
}
