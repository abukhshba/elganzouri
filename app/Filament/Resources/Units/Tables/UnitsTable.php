<?php

namespace App\Filament\Resources\Units\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('is_base', true))
            ->columns([
                TextColumn::make('name')
                    ->label(__('erp.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('short_name')
                    ->label(__('erp.fields.code'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unitGroup.name')
                    ->label(__('erp.nav_group.catalog_settings'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_base')
                    ->label(__('erp.fields.base_unit'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit_group_id')
                    ->relationship('unitGroup', 'name')
                    ->label(__('erp.nav_group.catalog_settings')),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
