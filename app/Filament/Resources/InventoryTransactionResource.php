<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryTransactionResource\Pages;
use App\Models\InventoryTransaction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryTransactionResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Inventory Ledger';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema; // Read-only ledger
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Timestamp'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'IN', 'RETURN_IN', 'ADJUSTMENT_IN', 'TRANSFER_IN' => 'success',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty (Base)')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Value')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_cost_after')
                    ->label('WAC After')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('performedBy.name')
                    ->label('User')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label('Warehouse'),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options(\App\Enums\InventoryTransactionType::options())
                    ->label('Transaction Type'),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only ledger, no bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryTransactions::route('/'),
        ];
    }
}
