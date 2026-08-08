<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BrandForm
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
                TextInput::make('slug')
                    ->label(__('erp.fields.code'))
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->label(__('erp.fields.is_active'))
                    ->default(true)
                    ->required(),
                Textarea::make('description.ar')
                    ->label(__('erp.fields.description'))
                    ->visible(fn (): bool => app()->getLocale() === 'ar')
                    ->columnSpanFull(),
                Textarea::make('description.en')
                    ->label(__('erp.fields.description'))
                    ->visible(fn (): bool => app()->getLocale() === 'en')
                    ->columnSpanFull(),
            ]);
    }
}
