<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Filament\Resources\SalesResource\Pages;
use App\Models\Sale;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SalesResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static \UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'فاتورة مبيعات' : 'Sales Invoice';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'فواتير المبيعات' : 'Sales Invoices';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (SaleStatus $state): string => match ($state) {
                        SaleStatus::CONFIRMED => 'success',
                        SaleStatus::CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::PAID => 'success',
                        PaymentStatus::PARTIAL => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_profit')
                    ->label('Gross Profit')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer'),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label('Warehouse'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(SaleStatus::options()),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
