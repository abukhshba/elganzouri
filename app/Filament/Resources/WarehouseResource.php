<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-home-modern';

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'مخزن' : 'Warehouse';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'المخازن' : 'Warehouses';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                Components\TextInput::make('code')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->label('Location Code (e.g. WH-MAIN)'),
                Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(30),
                Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Components\Textarea::make('address')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
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
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWarehouses::route('/'),
        ];
    }
}
