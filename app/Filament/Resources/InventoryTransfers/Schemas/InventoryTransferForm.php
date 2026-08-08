<?php

namespace App\Filament\Resources\InventoryTransfers\Schemas;

use App\Enums\TransferStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('erp.resources.inventory_transfer'))
                    ->schema([
                        TextInput::make('transfer_number')
                            ->label(__('erp.fields.code'))
                            ->default(fn () => 'TRF-'.strtoupper(bin2hex(random_bytes(4))))
                            ->readOnly()
                            ->required(),
                        Select::make('from_warehouse_id')
                            ->label(__('erp.fields.source_warehouse'))
                            ->relationship('fromWarehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('to_warehouse_id')
                            ->label(__('erp.fields.target_warehouse'))
                            ->relationship('toWarehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label(__('erp.fields.status'))
                            ->options(TransferStatus::options())
                            ->default(TransferStatus::DRAFT->value)
                            ->required(),
                        DateTimePicker::make('shipped_at')
                            ->label(__('erp.fields.issue_date')),
                        DateTimePicker::make('received_at')
                            ->label(__('erp.fields.timestamp')),
                        Textarea::make('notes')
                            ->label(__('erp.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
