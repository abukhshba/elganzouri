<?php

namespace App\Filament\Resources\OpeningBalances\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OpeningBalanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('opening_number')->label(__('erp.fields.code'))->badge(),
                        TextEntry::make('warehouse.name')->label(__('erp.resources.warehouse')),
                        TextEntry::make('issue_date')->label(__('erp.fields.issue_date'))->date(),
                        TextEntry::make('total_value')->label(__('erp.fields.stock_value'))->money('EGP'),
                        TextEntry::make('user.name')->label(__('erp.fields.user')),
                        TextEntry::make('notes')->label(__('erp.fields.description'))->placeholder('—'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                RepeatableEntry::make('items')
                    ->label(__('erp.resources.items'))
                    ->table([
                        RepeatableEntry\TableColumn::make(__('erp.fields.sku')),
                        RepeatableEntry\TableColumn::make(__('erp.resources.item')),
                        RepeatableEntry\TableColumn::make(__('erp.resources.unit')),
                        RepeatableEntry\TableColumn::make(__('erp.fields.quantity')),
                        RepeatableEntry\TableColumn::make(__('erp.fields.unit_cost')),
                        RepeatableEntry\TableColumn::make(__('erp.fields.selling_price')),
                        RepeatableEntry\TableColumn::make(__('erp.fields.total_amount')),
                    ])
                    ->schema([
                        TextEntry::make('item.sku'),
                        TextEntry::make('item.name'),
                        TextEntry::make('measurementUnitDetail.name'),
                        TextEntry::make('quantity')->numeric(2),
                        TextEntry::make('unit_cost')->money('EGP'),
                        TextEntry::make('selling_price')->money('EGP')->placeholder('—'),
                        TextEntry::make('line_total')->money('EGP'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
