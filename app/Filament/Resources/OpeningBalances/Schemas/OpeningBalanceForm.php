<?php

namespace App\Filament\Resources\OpeningBalances\Schemas;

use App\Models\Item;
use App\Models\MeasurementUnitDetail;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OpeningBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.opening_balance'))
                    ->schema([
                        Select::make('warehouse_id')
                            ->label(__('erp.resources.warehouse'))
                            ->options(fn () => Warehouse::query()->orderBy('id')->get()->pluck('name', 'id'))
                            ->default(fn () => Warehouse::query()->orderBy('id')->value('id'))
                            ->searchable()
                            ->required(),
                        DatePicker::make('issue_date')
                            ->label(__('erp.fields.issue_date'))
                            ->default(now())
                            ->required(),
                        Textarea::make('notes')
                            ->label(__('erp.fields.description'))
                            ->rows(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make(__('erp.resources.items'))
                    ->schema([
                        Repeater::make('lines')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('item_id')
                                    ->label(__('erp.resources.item'))
                                    ->searchable()
                                    ->getSearchResultsUsing(fn (string $search): array => Item::query()
                                        ->where(fn ($query) => $query->where('sku', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn (Item $item) => [$item->id => "{$item->sku} — {$item->name}"])
                                        ->all())
                                    ->getOptionLabelUsing(fn ($value): ?string => ($item = Item::find($value)) ? "{$item->sku} — {$item->name}" : null)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, $set) => $set('measurement_unit_detail_id', $state
                                        ? Item::with('measurementUnit.details')->find($state)?->measurementUnit?->details->firstWhere('is_default', true)?->id
                                        : null))
                                    ->columnSpan(2),
                                Select::make('measurement_unit_detail_id')
                                    ->label(__('erp.resources.unit'))
                                    ->options(fn ($get) => $get('item_id')
                                        ? Item::with('measurementUnit.details')->find($get('item_id'))?->measurementUnit?->details->pluck('name', 'id') ?? []
                                        : MeasurementUnitDetail::all()->pluck('name', 'id'))
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label(__('erp.fields.quantity'))
                                    ->numeric()
                                    ->minValue(0.0001)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($get, $set) => self::updateLine($get, $set)),
                                TextInput::make('unit_cost')
                                    ->label(__('erp.fields.unit_cost'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('EGP')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($get, $set) => self::updateLine($get, $set)),
                                TextInput::make('selling_price')
                                    ->label(__('erp.fields.selling_price'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('EGP'),
                                TextInput::make('line_total')
                                    ->label(__('erp.fields.total_amount'))
                                    ->prefix('EGP')
                                    ->readOnly()
                                    ->dehydrated(false),
                            ])
                            ->columns(7)
                            ->defaultItems(1)
                            ->minItems(1)
                            ->live()
                            ->afterStateUpdated(fn ($get, $set) => self::updateTotal($get, $set)),

                        TextInput::make('total_value')
                            ->label(__('erp.fields.stock_value'))
                            ->prefix('EGP')
                            ->readOnly()
                            ->dehydrated(false),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function updateLine($get, $set): void
    {
        $set('line_total', number_format((float) $get('quantity') * (float) $get('unit_cost'), 2, '.', ''));
        self::updateTotal($get, $set, '../../');
    }

    protected static function updateTotal($get, $set, string $path = ''): void
    {
        $total = collect($get($path.'lines') ?? [])->sum(fn ($line) => (float) ($line['quantity'] ?? 0) * (float) ($line['unit_cost'] ?? 0));
        $set($path.'total_value', number_format($total, 2, '.', ''));
    }
}
