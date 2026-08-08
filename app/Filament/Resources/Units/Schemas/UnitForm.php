<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.unit'))
                    ->schema([
                        Select::make('unit_group_id')
                            ->label(__('erp.nav_group.catalog_settings'))
                            ->relationship('unitGroup', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name.ar')
                            ->label(__('erp.fields.name'))
                            ->visible(fn (): bool => app()->getLocale() === 'ar')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('name.en')
                            ->label(__('erp.fields.name'))
                            ->visible(fn (): bool => app()->getLocale() === 'en')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('short_name')
                            ->label(__('erp.fields.code'))
                            ->required()
                            ->maxLength(15),
                        Toggle::make('is_base')
                            ->label(__('erp.fields.base_unit'))
                            ->default(false),
                        Toggle::make('is_custom_per_item')
                            ->label(__('erp.fields.type'))
                            ->default(false),
                        TextInput::make('global_conversion_factor')
                            ->label(__('erp.fields.amount'))
                            ->numeric()
                            ->default(1.0000)
                            ->required(),
                    ])
                    ->columnSpanFull()
                    ->columns(3),
            ]);
    }
}
