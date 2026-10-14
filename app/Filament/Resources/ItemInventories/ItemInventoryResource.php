<?php

namespace App\Filament\Resources\ItemInventories;

use App\Filament\Resources\ItemInventories\Pages;
use App\Filament\Resources\ItemInventories\RelationManagers\StockMovementsRelationManager;
use App\Filament\Resources\ItemInventories\Schemas\ItemInventoryInfolist;
use App\Filament\Resources\ItemInventories\Tables\ItemInventoriesTable;
use App\Models\ItemInventory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Inventory Audit Logs: current stock per item and warehouse, with the movements that produced it.
 */
class ItemInventoryResource extends Resource
{
    protected static ?string $model = ItemInventory::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav_group.inventory');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.inventory_transaction');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.inventory_transactions');
    }

    public static function canCreate(): bool
    {
        // Stock records are created by the posting service only
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItemInventoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemInventoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItemInventories::route('/'),
            'view' => Pages\ViewItemInventory::route('/{record}'),
        ];
    }
}
