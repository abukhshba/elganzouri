<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Cashbox;
use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\Warehouse;
use App\Services\UomService;
use Filament\Forms\Components\Checkbox;
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
                Section::make(__('erp.resources.supplier'))
                    ->schema([
                        Checkbox::make('is_new_supplier')
                            ->label(__('erp.fields.new_supplier'))
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Select::make('supplier_id')
                            ->label(__('erp.resources.supplier'))
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get): bool => ! (bool) $get('is_new_supplier'))
                            ->required(fn ($get): bool => ! (bool) $get('is_new_supplier')),

                        TextInput::make('new_supplier_name')
                            ->label(__('erp.fields.name').' ('.__('erp.resources.supplier').')')
                            ->visible(fn ($get): bool => (bool) $get('is_new_supplier'))
                            ->required(fn ($get): bool => (bool) $get('is_new_supplier'))
                            ->maxLength(150),

                        TextInput::make('new_supplier_phone')
                            ->label(__('erp.fields.phone'))
                            ->tel()
                            ->visible(fn ($get): bool => (bool) $get('is_new_supplier'))
                            ->required(fn ($get): bool => (bool) $get('is_new_supplier'))
                            ->maxLength(30),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('erp.resources.purchase'))
                    ->schema([
                        Select::make('warehouse_id')
                            ->label(__('erp.resources.warehouse'))
                            ->relationship('warehouse', 'name')
                            ->default(fn () => Warehouse::query()->orderBy('id')->value('id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('issue_date')
                            ->label(__('erp.fields.issue_date'))
                            ->default(now())
                            ->required(),
                        DatePicker::make('due_date')
                            ->label(__('erp.fields.due_date')),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make(__('erp.resources.items'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::computeLine($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::computeLine($data))
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
                                            return MeasurementUnitDetail::pluck('name', 'id');
                                        }

                                        $item = Item::with('measurementUnit.details')->find($itemId);
                                        if (! $item || ! $item->measurementUnit) {
                                            return MeasurementUnitDetail::pluck('name', 'id');
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
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $set('line_total', number_format((float) $state * (float) ($get('unit_price') ?? 0), 2, '.', ''));
                                        self::updateTotals($get, $set, '../../');
                                    }),
                                TextInput::make('unit_price')
                                    ->label(__('erp.fields.unit_cost'))
                                    ->numeric()
                                    ->prefix('EGP')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $set('line_total', number_format((float) ($get('quantity') ?? 0) * (float) $state, 2, '.', ''));
                                        self::updateTotals($get, $set, '../../');
                                    }),
                                TextInput::make('line_total')
                                    ->label(__('erp.fields.total_amount'))
                                    ->numeric()
                                    ->prefix('EGP')
                                    ->readOnly(),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(fn ($get, $set) => self::updateTotals($get, $set))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('erp.fields.total_amount'))
                    ->schema([
                        Select::make('cashbox_id')
                            ->label(__('erp.resources.cashbox'))
                            ->relationship('cashbox', 'name', fn ($query) => $query->where('is_active', true))
                            ->default(fn () => Cashbox::where('is_active', true)->orderBy('id')->value('id'))
                            ->searchable()
                            ->preload()
                            ->required(fn ($get): bool => (float) $get('paid_amount') > 0),

                        TextInput::make('total_amount')
                            ->label(__('erp.fields.total_amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00)
                            ->readOnly(),

                        Checkbox::make('is_paid')
                            ->label(__('erp.fields.paid_in_full'))
                            ->default(false)
                            ->live()
                            ->afterStateHydrated(fn ($record, $set) => $set('is_paid', $record
                                && (float) $record->total_amount > 0
                                && (float) $record->paid_amount >= (float) $record->total_amount))
                            ->afterStateUpdated(function ($state, $get, $set) {
                                if ($state) {
                                    $set('paid_amount', $get('total_amount'));
                                }
                                self::updateDue($get, $set);
                            }),

                        TextInput::make('paid_amount')
                            ->label(__('erp.fields.paid_amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00)
                            ->minValue(0)
                            ->readOnly(fn ($get): bool => (bool) $get('is_paid'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($get, $set) => self::updateDue($get, $set)),

                        TextInput::make('due_amount')
                            ->label(__('erp.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0.00)
                            ->readOnly(),

                        Textarea::make('notes')
                            ->label(__('erp.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Recalculate the invoice total from all lines. $path is '../../' when called from inside a repeater row.
     */
    public static function updateTotals($get, $set, string $path = ''): void
    {
        $total = 0.0;

        foreach ($get($path.'items') ?? [] as $item) {
            $total += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }

        $set($path.'total_amount', number_format($total, 2, '.', ''));

        if ((bool) $get($path.'is_paid')) {
            $set($path.'paid_amount', number_format($total, 2, '.', ''));
        }

        self::updateDue($get, $set, $path);
    }

    public static function updateDue($get, $set, string $path = ''): void
    {
        $due = max(0, (float) $get($path.'total_amount') - (float) $get($path.'paid_amount'));
        $set($path.'due_amount', number_format($due, 2, '.', ''));
    }

    /**
     * Fill the computed base-unit columns of a purchase line before it is stored.
     */
    public static function computeLine(array $data): array
    {
        $uom = app(UomService::class);
        $qty = (float) ($data['quantity'] ?? 0);
        $price = (float) ($data['unit_price'] ?? 0);
        $unitDetailId = (int) $data['measurement_unit_detail_id'];

        $data['line_total'] = round($qty * $price, 4);
        $data['conversion_factor'] = $uom->getConversionFactor($unitDetailId);
        $data['base_quantity'] = $uom->convertQuantityToBaseUnit($unitDetailId, $qty);
        $data['base_unit_cost'] = $uom->convertPriceToBaseUnit($unitDetailId, $price);

        return $data;
    }
}
