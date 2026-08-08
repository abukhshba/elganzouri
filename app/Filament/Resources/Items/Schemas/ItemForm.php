<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Item;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    TextInput::make('name.ar')
                        ->label(__('erp.fields.name'))
                        ->visible(fn (): bool => app()->getLocale() === 'ar')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('name.en')
                        ->label(__('erp.fields.name'))
                        ->visible(fn (): bool => app()->getLocale() === 'en')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('sku')
                        ->label(__('erp.fields.sku'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(60),
                    TextInput::make('barcode')
                        ->label(__('erp.fields.barcode'))
                        ->maxLength(60),
                    Select::make('category_id')
                        ->label(__('erp.fields.category'))
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('brand_id')
                        ->label(__('erp.fields.brand'))
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('base_unit_id')
                        ->label(__('erp.fields.base_unit'))
                        ->relationship('baseUnit', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (?Item $record) => $record !== null),
                    TextInput::make('min_stock_alert')
                        ->label(__('erp.fields.min_stock_alert'))
                        ->numeric()
                        ->default(0.0000),
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
                ])
                ->columns(2)
                ->columnSpanFull(),
            ]);
    }
}
