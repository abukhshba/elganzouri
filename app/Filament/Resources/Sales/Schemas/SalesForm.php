<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.sale'))
                    ->schema([
                        Checkbox::make('is_new_customer')
                            ->label(__('erp.fields.new_customer') ?? 'عميل جديد')
                            ->default(false)
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Select::make('customer_id')
                            ->label(__('erp.resources.customer'))
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get): bool => ! (bool) $get('is_new_customer'))
                            ->required(fn ($get): bool => ! (bool) $get('is_new_customer')),

                        TextInput::make('new_customer_name')
                            ->label(__('erp.fields.name').' (العميل)')
                            ->visible(fn ($get): bool => (bool) $get('is_new_customer'))
                            ->required(fn ($get): bool => (bool) $get('is_new_customer'))
                            ->dehydrated(false)
                            ->maxLength(150),

                        TextInput::make('new_customer_phone')
                            ->label(__('erp.fields.phone'))
                            ->tel()
                            ->visible(fn ($get): bool => (bool) $get('is_new_customer'))
                            ->required(fn ($get): bool => (bool) $get('is_new_customer'))
                            ->dehydrated(false)
                            ->maxLength(30),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('erp.resources.items'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('item_id')
                                    ->label(__('erp.resources.item'))
                                    ->relationship('item', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('warehouse_id')
                                    ->label(__('erp.resources.warehouse'))
                                    ->relationship('warehouse', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('unit_id')
                                    ->label(__('erp.resources.unit'))
                                    ->relationship('unit', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label(__('erp.fields.quantity'))
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $price = (float) ($get('unit_price') ?? 0);
                                        $set('line_total', number_format((float) $state * $price, 2, '.', ''));
                                        self::updateTotals($get, $set);
                                    }),

                                TextInput::make('unit_price')
                                    ->label(__('erp.fields.unit_cost'))
                                    ->numeric()
                                    ->prefix('EGP')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $qty = (float) ($get('quantity') ?? 0);
                                        $set('line_total', number_format($qty * (float) $state, 2, '.', ''));
                                        self::updateTotals($get, $set);
                                    }),

                                TextInput::make('line_total')
                                    ->label(__('erp.fields.total_amount'))
                                    ->numeric()
                                    ->prefix('EGP')
                                    ->readOnly(),
                            ])
                            ->columns(7)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(fn ($get, $set) => self::updateTotals($get, $set))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('erp.fields.payment') ?? 'الدفع')
                    ->schema([
                        Select::make('cashbox_id')
                            ->label(__('erp.resources.cashbox'))
                            ->relationship('cashbox', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TextInput::make('total_amount')
                            ->label(__('erp.fields.total_amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00)
                            ->readOnly(),

                        Checkbox::make('is_paid')
                            ->label(__('erp.fields.paid_in_full') ?? 'مدفوع بالكامل')
                            ->default(true)
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $get, $set) {
                                if ($state) {
                                    $set('paid_amount', $get('total_amount'));
                                }
                            }),

                        TextInput::make('paid_amount')
                            ->label(__('erp.fields.paid_amount') ?? 'المبلغ المدفوع')
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00)
                            ->readOnly(fn ($get): bool => (bool) $get('is_paid'))
                            ->live(onBlur: true),

                        Textarea::make('notes')
                            ->label(__('erp.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function updateTotals($get, $set): void
    {
        $items = $get('items') ?? [];
        $total = 0.0;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $total += ($qty * $price);
        }

        $formattedTotal = number_format($total, 2, '.', '');
        $set('total_amount', $formattedTotal);

        if ((bool) $get('is_paid')) {
            $set('paid_amount', $formattedTotal);
        }
    }
}
