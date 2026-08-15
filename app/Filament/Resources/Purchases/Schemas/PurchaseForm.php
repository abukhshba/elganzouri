<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Models\Item;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.purchase'))
                    ->schema([
                        TextInput::make('purchase_number')
                            ->label(__('erp.fields.invoice_number'))
                            ->default(fn () => 'PUR-'.strtoupper(bin2hex(random_bytes(4))))
                            ->readOnly()
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
                        DatePicker::make('issue_date')
                            ->label(__('erp.fields.issue_date'))
                            ->default(now())
                            ->required(),
                        DatePicker::make('due_date')
                            ->label(__('erp.fields.created_at')),
                        Select::make('status')
                            ->label(__('erp.fields.status'))
                            ->options(PurchaseStatus::options())
                            ->default(PurchaseStatus::CONFIRMED->value)
                            ->required(),
                        Select::make('payment_status')
                            ->label(__('erp.fields.payment_status'))
                            ->options(PaymentStatus::options())
                            ->default(PaymentStatus::UNPAID->value)
                            ->required(),
                    ])
                    ->columns(3),

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
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $item = Item::with('measurementUnit.details')->find($state);
                                            if ($item && $item->measurementUnit) {
                                                $defaultDetail = $item->measurementUnit->details->where('is_default', true)->first();
                                                if ($defaultDetail) {
                                                    $set('measurement_unit_detail_id', $defaultDetail->id);
                                                }
                                            }
                                        }
                                    }),
                                Select::make('measurement_unit_detail_id')
                                    ->label(__('erp.resources.unit'))
                                    ->options(function ($get) {
                                        $itemId = $get('item_id');
                                        if (! $itemId) {
                                            return \App\Models\MeasurementUnitDetail::pluck('name', 'id');
                                        }

                                        $item = Item::with('measurementUnit.details')->find($itemId);
                                        if (! $item || ! $item->measurementUnit) {
                                            return \App\Models\MeasurementUnitDetail::pluck('name', 'id');
                                        }

                                        return $item->measurementUnit->details->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label(__('erp.fields.quantity'))
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                TextInput::make('unit_price')
                                    ->label(__('erp.fields.unit_cost'))
                                    ->numeric()
                                    ->prefix('EGP')
                                    ->required(),
                                TextInput::make('line_total')
                                    ->label(__('erp.fields.total_amount'))
                                    ->numeric()
                                    ->prefix('EGP'),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('erp.fields.total_amount'))
                    ->schema([
                        TextInput::make('total_amount')
                            ->label(__('erp.fields.total_amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00),
                        TextInput::make('tax_amount')
                            ->label(__('erp.fields.amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00),
                        TextInput::make('discount_amount')
                            ->label(__('erp.fields.amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00),
                        TextInput::make('paid_amount')
                            ->label(__('erp.fields.amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00),
                        Textarea::make('notes')
                            ->label(__('erp.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
            ]);
    }
}
