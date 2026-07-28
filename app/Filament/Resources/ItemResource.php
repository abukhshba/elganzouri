<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Models\Item;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Table;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'صنف' : 'Item';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الأصناف' : 'Items';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Group::make([
                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Components\TextInput::make('sku')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(60)
                        ->label('SKU'),
                    Components\TextInput::make('barcode')
                        ->maxLength(60),
                    Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Components\Select::make('brand_id')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Components\Select::make('base_unit_id')
                        ->label('Immutable Base Unit of Measure')
                        ->relationship('baseUnit', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (?Item $record) => $record !== null), // Locked after creation
                    Components\TextInput::make('min_stock_alert')
                        ->numeric()
                        ->default(0.0000)
                        ->label('Re-order Alert Level (Base Units)'),
                    Components\Toggle::make('is_active')
                        ->default(true)
                        ->required(),
                    Components\Textarea::make('description')
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('baseUnit.short_name')
                    ->label('Base Unit')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
