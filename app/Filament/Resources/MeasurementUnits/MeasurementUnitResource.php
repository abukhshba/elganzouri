<?php

namespace App\Filament\Resources\MeasurementUnits;

use App\Filament\Resources\MeasurementUnits\Pages;
use App\Models\MeasurementUnit;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MeasurementUnitResource extends Resource
{
    protected static ?string $model = MeasurementUnit::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav_group.catalog_settings');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.unit'); // Will translate to "Measurement Unit" in custom lang later
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.units');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name.ar')
                    ->label(__('erp.fields.name') . ' (AR)')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state && empty($set('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('name.en')
                    ->label(__('erp.fields.name') . ' (EN)')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state && empty($set('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->label(__('erp.fields.slug') ?? 'Slug')
                    ->required()
                    ->unique(ignoreRecord: true),

            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('erp.fields.name'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('erp.fields.slug') ?? 'Slug')
                    ->searchable(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\MeasurementUnits\RelationManagers\DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeasurementUnits::route('/'),
            'create' => Pages\CreateMeasurementUnit::route('/create'),
            'edit' => Pages\EditMeasurementUnit::route('/{record}/edit'),
        ];
    }
}
