<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';

    protected static \UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'مورد' : 'Supplier';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الموردين' : 'Suppliers';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->required()
                    ->maxLength(150),
                Components\TextInput::make('company_name')
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
                Components\TextInput::make('balance')
                    ->numeric()
                    ->disabled()
                    ->label('Accounts Payable Debt Balance (EGP)'),
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
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('AP Debt Balance')
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
            'index' => Pages\ManageSuppliers::route('/'),
        ];
    }
}
