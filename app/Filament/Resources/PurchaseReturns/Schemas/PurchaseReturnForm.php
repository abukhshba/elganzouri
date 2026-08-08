<?php

namespace App\Filament\Resources\PurchaseReturns\Schemas;

use App\Enums\ReturnStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.purchase_return'))
                    ->schema([
                        TextInput::make('return_number')
                            ->label(__('erp.fields.return_number'))
                            ->default(fn () => 'PRET-'.strtoupper(bin2hex(random_bytes(4))))
                            ->readOnly()
                            ->required(),
                        Select::make('purchase_id')
                            ->label(__('erp.fields.original_purchase'))
                            ->relationship('purchase', 'purchase_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('supplier_id')
                            ->label(__('erp.resources.supplier'))
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('warehouse_id')
                            ->label(__('erp.resources.warehouse'))
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label(__('erp.fields.status'))
                            ->options(ReturnStatus::options())
                            ->default(ReturnStatus::CONFIRMED->value)
                            ->required(),
                        TextInput::make('total_amount')
                            ->label(__('erp.fields.total_amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00),
                        TextInput::make('refunded_amount')
                            ->label(__('erp.fields.refund_amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00),
                        Textarea::make('notes')
                            ->label(__('erp.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
