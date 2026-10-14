<?php

namespace App\Filament\Resources\OpeningBalances;

use App\Filament\Resources\OpeningBalances\Schemas\OpeningBalanceForm;
use App\Filament\Resources\OpeningBalances\Schemas\OpeningBalanceInfolist;
use App\Filament\Resources\OpeningBalances\Tables\OpeningBalancesTable;
use App\Models\OpeningBalance;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OpeningBalanceResource extends Resource
{
    protected static ?string $model = OpeningBalance::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav_group.inventory');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.opening_balance');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.opening_balances');
    }

    // Opening balances post stock immediately, so they are never changed afterwards
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return OpeningBalanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OpeningBalanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpeningBalancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpeningBalances::route('/'),
            'create' => Pages\CreateOpeningBalance::route('/create'),
            'view' => Pages\ViewOpeningBalance::route('/{record}'),
        ];
    }
}
