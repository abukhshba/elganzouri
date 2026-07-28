<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'عميل' : 'Customer';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'العملاء' : 'Customers';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->required()
                    ->maxLength(150),
                Components\TextInput::make('email')
                    ->email()
                    ->maxLength(100),
                Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(30),
                Components\TextInput::make('tax_number')
                    ->maxLength(50)
                    ->label('Tax Identification Number'),
                Components\Select::make('payment_term_id')
                    ->relationship('paymentTerm', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Components\TextInput::make('credit_limit')
                    ->numeric()
                    ->default(0.0000)
                    ->label('Credit Limit (EGP)'),
                Components\TextInput::make('balance')
                    ->numeric()
                    ->disabled()
                    ->label('Accounts Receivable Debt Balance (EGP)'),
                Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Components\Textarea::make('address')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('AR Debt Balance')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCustomers::route('/'),
        ];
    }
}
