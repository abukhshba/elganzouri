<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'فاتورة شراء' : 'Purchase Invoice';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'فواتير الشراء' : 'Purchase Invoices';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchase_number')
                    ->label('Invoice #')
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
                    ->color(fn (PurchaseStatus $state): string => match ($state) {
                        PurchaseStatus::CONFIRMED => 'success',
                        PurchaseStatus::CANCELLED => 'danger',
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
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier'),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label('Warehouse'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(PurchaseStatus::options()),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
