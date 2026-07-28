<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog Settings';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'ماركة' : 'Brand';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الماركات' : 'Brands';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
            'index' => Pages\ManageBrands::route('/'),
        ];
    }
}
