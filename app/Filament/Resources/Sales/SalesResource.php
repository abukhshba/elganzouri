<?php

namespace App\Filament\Resources\Sales;

use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\Pages;
use App\Filament\Resources\Sales\Schemas\SalesForm;
use App\Filament\Resources\Sales\Tables\SalesTable;
use App\Filament\RelationManagers\InvoicePaymentsRelationManager;
use App\Models\Sale;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SalesResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav_group.sales');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.sale');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.sales');
    }

    public static function canEdit(Model $record): bool
    {
        // Confirmed sales have posted stock, cash and AR; changing them would desync the ledgers
        return $record->status !== SaleStatus::CONFIRMED && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record->status !== SaleStatus::CONFIRMED && parent::canDelete($record);
    }

    public static function form(Schema $schema): Schema
    {
        return SalesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvoicePaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
