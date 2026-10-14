<?php

namespace App\Filament\Resources\ItemInventories\RelationManagers;

use App\Enums\InventoryTransactionType;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Resources\Sales\SalesResource;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\Sale;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every stock movement of one item in one warehouse, oldest first, ending at the current stock.
 */
class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('erp.fields.movements');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['reference', 'performedBy']))
            ->defaultSort('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('erp.fields.timestamp'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label(__('erp.fields.type'))
                    ->badge()
                    ->color(fn (InventoryTransactionType $state): string => $state->isInflow() ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('erp.fields.quantity_change'))
                    ->formatStateUsing(fn (InventoryTransaction $record): string => ($record->transaction_type->isInflow() ? '+' : '−').number_format((float) $record->quantity, 2))
                    ->color(fn (InventoryTransaction $record): string => $record->transaction_type->isInflow() ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label(__('erp.fields.stock_after'))
                    ->numeric(2)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label(__('erp.fields.unit_cost'))
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('average_cost_after')
                    ->label(__('erp.fields.avg_cost_after'))
                    ->money('EGP')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')
                    ->label(__('erp.fields.source_document'))
                    ->state(fn (InventoryTransaction $record): string => self::sourceLabel($record))
                    ->url(fn (InventoryTransaction $record): ?string => self::sourceUrl($record))
                    ->color(fn (InventoryTransaction $record): ?string => self::sourceUrl($record) ? 'primary' : null),
                Tables\Columns\TextColumn::make('performedBy.name')
                    ->label(__('erp.fields.user'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label(__('erp.fields.description'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label(__('erp.fields.type'))
                    ->options(InventoryTransactionType::options()),
            ]);
    }

    protected static function sourceLabel(InventoryTransaction $record): string
    {
        return match (true) {
            $record->reference instanceof Sale => $record->reference->invoice_number,
            $record->reference instanceof Purchase => $record->reference->purchase_number,
            default => class_basename($record->reference_type).' #'.$record->reference_id,
        };
    }

    protected static function sourceUrl(InventoryTransaction $record): ?string
    {
        return match (true) {
            $record->reference instanceof Sale => SalesResource::getUrl('view', ['record' => $record->reference]),
            $record->reference instanceof Purchase => PurchaseResource::getUrl('view', ['record' => $record->reference]),
            default => null,
        };
    }
}
