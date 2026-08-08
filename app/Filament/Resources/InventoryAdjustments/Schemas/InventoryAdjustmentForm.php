<?php

namespace App\Filament\Resources\InventoryAdjustments\Schemas;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.inventory_adjustment'))
                    ->schema([
                        TextInput::make('adjustment_number')
                            ->label(__('erp.fields.code'))
                            ->default(fn () => 'ADJ-'.strtoupper(bin2hex(random_bytes(4))))
                            ->readOnly()
                            ->required(),
                        Select::make('warehouse_id')
                            ->label(__('erp.resources.warehouse'))
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('adjustment_type')
                            ->label(__('erp.fields.type'))
                            ->options(AdjustmentType::options())
                            ->required(),
                        Select::make('reason')
                            ->label(__('erp.fields.reason'))
                            ->options(AdjustmentReason::options())
                            ->required(),
                        Textarea::make('notes')
                            ->label(__('erp.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
