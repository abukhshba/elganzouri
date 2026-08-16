<?php

namespace App\Filament\Resources\Sales\RelationManagers;

use App\Enums\CashboxTransactionType;
use App\Enums\PaymentStatus;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Sale;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('erp.fields.payments');
    }

    public function table(Table $table): Table
    {
        /** @var Sale $sale */
        $sale = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('cashbox.name')
                    ->label(__('erp.resources.cashbox'))
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('erp.fields.amount'))
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->label(__('erp.fields.type'))
                    ->badge()
                    ->color(fn (CashboxTransactionType $state): string => match ($state) {
                        CashboxTransactionType::IN => 'success',
                        CashboxTransactionType::OUT => 'danger',
                    }),
                TextColumn::make('user.name')
                    ->label(__('erp.fields.name'))
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('erp.fields.description'))
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('erp.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('erp.actions.add_payment'))
                    ->visible(fn (): bool => $sale->remaining_balance > 0)
                    ->form([
                        Select::make('cashbox_id')
                            ->label(__('erp.resources.cashbox'))
                            ->options(fn () => Cashbox::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('amount')
                            ->label(__('erp.fields.amount'))
                            ->numeric()
                            ->prefix('EGP')
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn (): float => $sale->remaining_balance)
                            ->default(fn (): float => $sale->remaining_balance)
                            ->helperText(fn (): string => __('erp.fields.remaining_balance') . ': ' . number_format($sale->remaining_balance, 2) . ' EGP'),

                        Textarea::make('description')
                            ->label(__('erp.fields.description'))
                            ->default(fn (): string => "دفعة فاتورة مبيعات رقم #{$sale->invoice_number}"),
                    ])
                    ->mutateFormDataUsing(function (array $data) use ($sale): array {
                        $cashbox = Cashbox::findOrFail($data['cashbox_id']);
                        $amount = (float) $data['amount'];
                        $balanceBefore = (float) $cashbox->current_balance;
                        $balanceAfter = $balanceBefore + $amount;

                        $data['transaction_type'] = CashboxTransactionType::IN;
                        $data['reference_type'] = Sale::class;
                        $data['reference_id'] = $sale->id;
                        $data['balance_before'] = $balanceBefore;
                        $data['balance_after'] = $balanceAfter;
                        $data['user_id'] = auth()->id() ?? 1;
                        $data['created_at'] = now();

                        return $data;
                    })
                    ->after(function (CashboxTransaction $record) use ($sale): void {
                        // Update cashbox balance
                        $cashbox = Cashbox::find($record->cashbox_id);
                        if ($cashbox) {
                            $cashbox->update(['current_balance' => $record->balance_after]);
                        }

                        // Recalculate sale payment totals
                        $totalPaid = (float) $sale->payments()->sum('amount');
                        $totalAmount = (float) $sale->total_amount;
                        $dueAmount = max(0, $totalAmount - $totalPaid);

                        if ($totalPaid >= $totalAmount && $totalAmount > 0) {
                            $paymentStatus = PaymentStatus::PAID;
                        } elseif ($totalPaid > 0) {
                            $paymentStatus = PaymentStatus::PARTIAL;
                        } else {
                            $paymentStatus = PaymentStatus::UNPAID;
                        }

                        $sale->update([
                            'paid_amount' => $totalPaid,
                            'due_amount' => $dueAmount,
                            'payment_status' => $paymentStatus,
                        ]);
                    }),
            ]);
    }
}
