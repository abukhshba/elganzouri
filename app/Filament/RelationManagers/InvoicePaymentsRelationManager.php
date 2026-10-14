<?php

namespace App\Filament\RelationManagers;

use App\Enums\CashboxTransactionType;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Exceptions\CashboxBalanceException;
use App\Models\Cashbox;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\InvoicePaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Payments made against a sale or purchase invoice (its cashbox ledger rows).
 */
class InvoicePaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('erp.fields.payments');
    }

    public function isReadOnly(): bool
    {
        // Payments are added from the view page too
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('erp.fields.payment_date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cashbox.name')
                    ->label(__('erp.resources.cashbox')),
                TextColumn::make('transaction_type')
                    ->label(__('erp.fields.type'))
                    ->badge()
                    ->color(fn (CashboxTransactionType $state): string => match ($state) {
                        CashboxTransactionType::IN => 'success',
                        CashboxTransactionType::OUT => 'danger',
                    }),
                TextColumn::make('amount')
                    ->label(__('erp.fields.amount'))
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('erp.fields.user'))
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('erp.fields.description'))
                    ->limit(50)
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('addPayment')
                    ->label(__('erp.actions.add_payment'))
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (): bool => $this->canAddPayment())
                    ->schema(fn (): array => [
                        Select::make('cashbox_id')
                            ->label(__('erp.resources.cashbox'))
                            ->options(fn () => Cashbox::where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => $this->getOwnerRecord()->cashbox_id ?? Cashbox::where('is_active', true)->orderBy('id')->value('id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('amount')
                            ->label(__('erp.fields.amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn (): float => $this->remaining())
                            ->default(fn (): float => $this->remaining())
                            ->helperText(fn (): string => __('erp.fields.remaining_balance').': '.number_format($this->remaining(), 2).' EGP'),
                        Textarea::make('description')
                            ->label(__('erp.fields.description')),
                    ])
                    ->action(function (array $data, Action $action): void {
                        try {
                            app(InvoicePaymentService::class)->addPayment(
                                invoice: $this->getOwnerRecord(),
                                cashboxId: (int) $data['cashbox_id'],
                                amount: (float) $data['amount'],
                                userId: auth()->id(),
                                description: $data['description'] ?? null,
                            );
                        } catch (CashboxBalanceException $e) {
                            Notification::make()
                                ->title(__('erp.actions.add_payment_failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }

                        Notification::make()
                            ->title(__('erp.actions.add_payment_success'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function remaining(): float
    {
        return max(0, (float) $this->getOwnerRecord()->due_amount);
    }

    protected function canAddPayment(): bool
    {
        /** @var Sale|Purchase $invoice */
        $invoice = $this->getOwnerRecord();
        $confirmed = $invoice instanceof Sale
            ? $invoice->status === SaleStatus::CONFIRMED
            : $invoice->status === PurchaseStatus::CONFIRMED;

        return $confirmed && $this->remaining() > 0;
    }
}
