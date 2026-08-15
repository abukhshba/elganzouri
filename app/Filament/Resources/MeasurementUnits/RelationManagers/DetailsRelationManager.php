<?php

namespace App\Filament\Resources\MeasurementUnits\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name.ar')
                    ->label(__('erp.fields.name') . ' (AR)')
                    ->required(),
                TextInput::make('name.en')
                    ->label(__('erp.fields.name') . ' (EN)')
                    ->required(),
                Select::make('unit_type')
                    ->label(__('erp.fields.unit_type') ?? 'Unit Type')
                    ->options([
                        'weight' => 'Weight',
                        'length' => 'Length',
                        'numbers' => 'Numbers',
                        'volume' => 'Volume',
                    ])
                    ->required(),
                Toggle::make('is_default')
                    ->label(__('erp.fields.is_default') ?? 'Is Default')
                    ->live()
                    ->disabled(function (\Filament\Resources\RelationManagers\RelationManager $livewire, ?\Illuminate\Database\Eloquent\Model $record) {
                        if ($record && $record->is_default) {
                            return false;
                        }
                        return $livewire->getOwnerRecord()->details()->where('is_default', true)->exists();
                    })
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state) {
                            $set('value', 1);
                        }
                    }),
                TextInput::make('value')
                    ->label(__('erp.fields.value') ?? 'Conversion Value')
                    ->numeric()
                    ->required()
                    ->live()
                    ->disabled(fn ($get) => $get('is_default') === true)
                    ->dehydrated(true)
                    ->default(1)
                    ->unique(
                        modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, \Filament\Resources\RelationManagers\RelationManager $livewire) {
                            return $rule->where('measurement_unit_id', $livewire->getOwnerRecord()->id);
                        },
                        ignoreRecord: true
                    )
                    ->validationMessages([
                        'unique' => __('erp.validation.unique_conversion_value'),
                    ]),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('erp.fields.name'))
                    ->searchable(),
                TextColumn::make('unit_type')
                    ->label(__('erp.fields.unit_type'))
                    ->searchable(),
                IconColumn::make('is_default')
                    ->label(__('erp.fields.is_default'))
                    ->boolean(),
                TextColumn::make('value')
                    ->label(__('erp.fields.value'))
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!empty($data['is_default'])) {
                            $data['value'] = 1;
                        }
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!empty($data['is_default'])) {
                            $data['value'] = 1;
                        }
                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
