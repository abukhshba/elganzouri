<?php

namespace App\Filament\Resources\Cashboxes;

use App\Filament\Resources\Cashboxes\Pages;
use App\Filament\Resources\Cashboxes\Schemas\CashboxForm;
use App\Filament\Resources\Cashboxes\Tables\CashboxesTable;
use App\Models\Cashbox;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CashboxResource extends Resource
{
    protected static ?string $model = Cashbox::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav_group.treasury');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.cashbox');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.cashboxes');
    }

    public static function form(Schema $schema): Schema
    {
        return CashboxForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashboxesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCashboxes::route('/'),
        ];
    }
}
