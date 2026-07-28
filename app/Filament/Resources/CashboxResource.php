<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashboxResource\Pages;
use App\Models\Cashbox;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CashboxResource extends Resource
{
    protected static ?string $model = Cashbox::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Cashbox Registers';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->label('Assigned Cashier'),
                Components\TextInput::make('current_balance')
                    ->numeric()
                    ->default(0.0000)
                    ->label('Real-time Cash Balance (EGP)'),
                Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Cash Balance')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCashboxes::route('/'),
        ];
    }
}
