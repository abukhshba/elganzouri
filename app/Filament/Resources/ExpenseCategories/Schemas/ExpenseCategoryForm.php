<?php

namespace App\Filament\Resources\ExpenseCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseCategoryForm
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
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),
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
