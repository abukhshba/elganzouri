<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseCategoryResource\Pages;
use App\Models\ExpenseCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Expense Categories';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                Components\TextInput::make('code')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->label('GL Accounting Code (e.g. EXP-RENT)'),
                Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExpenseCategories::route('/'),
        ];
    }
}
