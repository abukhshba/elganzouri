<?php

namespace App\Filament\Resources\Units\RelationManagers;

use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DerivedUnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'derivedUnits';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('erp.resources.units');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name.ar')
                    ->label(__('erp.fields.name'))
                    ->visible(fn (): bool => app()->getLocale() === 'ar')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name.en')
                    ->label(__('erp.fields.name'))
                    ->visible(fn (): bool => app()->getLocale() === 'en')
                    ->required()
                    ->maxLength(50),
                TextInput::make('short_name')
                    ->label(__('erp.fields.code'))
                    ->required()
                    ->maxLength(15),
                Toggle::make('is_custom_per_item')
                    ->label(__('erp.fields.type'))
                    ->default(false),
                TextInput::make('global_conversion_factor')
                    ->label(__('erp.fields.amount'))
                    ->numeric()
                    ->default(1.0000)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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
                IconColumn::make('is_custom_per_item')
                    ->label(__('erp.fields.type'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('global_conversion_factor')
                    ->label(__('erp.fields.amount'))
                    ->numeric(4)
                    ->sortable(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['is_base'] = false;
                        $data['unit_group_id'] = $this->getOwnerRecord()->unit_group_id;
                        return $data;
                    }),
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
