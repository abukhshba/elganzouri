<?php

namespace App\Filament\Resources\InventoryTransfers;

use App\Filament\Resources\InventoryTransfers\Pages;
use App\Filament\Resources\InventoryTransfers\Schemas\InventoryTransferForm;
use App\Filament\Resources\InventoryTransfers\Tables\InventoryTransfersTable;
use App\Models\InventoryTransfer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InventoryTransferResource extends Resource
{
    protected static ?string $model = InventoryTransfer::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav_group.inventory');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.inventory_transfer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.inventory_transfers');
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryTransfersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryTransfers::route('/'),
            'create' => Pages\CreateInventoryTransfer::route('/create'),
            'edit' => Pages\EditInventoryTransfer::route('/{record}/edit'),
        ];
    }
}
