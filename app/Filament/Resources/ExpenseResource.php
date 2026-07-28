<?php

namespace App\Filament\Resources;

use App\Actions\Treasury\RecordExpenseAction;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Operating Expenses';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('expense_number')
                    ->disabled()
                    ->placeholder('Auto-generated (e.g. EXP-00001)'),
                Components\Select::make('expense_category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Components\Select::make('cashbox_id')
                    ->relationship('cashbox', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Source Cash Register'),
                Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label('Expense Amount (EGP)'),
                Components\DatePicker::make('expense_date')
                    ->default(now())
                    ->required(),
                Components\TextInput::make('reference_number')
                    ->maxLength(100)
                    ->label('External Bill / Receipt Ref'),
                Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_number')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cashbox.name')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expense_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Operator')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('cashbox_id')
                    ->relationship('cashbox', 'name')
                    ->label('Cashbox Register'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only vouchers, no bulk deletes
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
        ];
    }
}
