<?php

namespace App\Filament\Resources\ItemInventories;

use App\Filament\Resources\ItemInventories\Pages;
use App\Filament\Resources\ItemInventories\Schemas\ItemInventoryForm;
use App\Filament\Resources\ItemInventories\Tables\ItemInventoriesTable;
use App\Models\ItemInventory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ItemInventoryResource extends Resource
{
    protected static ?string $model = ItemInventory::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav_group.inventory');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.item_inventory');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.item_inventories');
    }

    public static function form(Schema $schema): Schema
    {
        return ItemInventoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemInventoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItemInventories::route('/'),
        ];
    }
}
