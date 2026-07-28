<?php

namespace App\Filament\Resources;

use App\Enums\ReturnStatus;
use App\Filament\Resources\PurchaseReturnResource\Pages;
use App\Models\PurchaseReturn;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static \UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?string $navigationLabel = 'Purchase Returns';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')
                    ->label('Return #')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ReturnStatus $state): string => match ($state) {
                        ReturnStatus::CONFIRMED => 'success',
                        ReturnStatus::CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('refunded_amount')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(ReturnStatus::options()),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseReturns::route('/'),
            'create' => Pages\CreatePurchaseReturn::route('/create'),
            'edit' => Pages\EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
