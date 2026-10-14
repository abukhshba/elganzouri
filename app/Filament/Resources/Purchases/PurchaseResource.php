<?php

namespace App\Filament\Resources\Purchases;

use App\Enums\PurchaseStatus;
use App\Filament\Resources\Purchases\Pages;
use App\Filament\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Resources\Purchases\Tables\PurchasesTable;
use App\Filament\RelationManagers\InvoicePaymentsRelationManager;
use App\Models\Purchase;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav_group.purchasing');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.purchase');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.purchases');
    }

    public static function canEdit(Model $record): bool
    {
        // Confirmed purchases have posted stock and AP; changing them would desync the ledgers
        return $record->status !== PurchaseStatus::CONFIRMED && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record->status !== PurchaseStatus::CONFIRMED && parent::canDelete($record);
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
