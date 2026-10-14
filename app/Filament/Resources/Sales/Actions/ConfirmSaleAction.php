<?php

namespace App\Filament\Resources\Sales\Actions;

use App\Actions\Sales\ProcessSaleInvoiceAction;
use App\Enums\SaleStatus;
use App\Exceptions\CashboxBalanceException;
use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidUomConversionException;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ConfirmSaleAction
{
    public static function make(): Action
    {
        return Action::make('confirm')
            ->label(__('erp.actions.confirm'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('erp.actions.confirm_sale_heading'))
            ->modalDescription(__('erp.actions.confirm_sale_description'))
            ->visible(fn (Sale $record): bool => $record->status === SaleStatus::DRAFT)
            ->action(function (Sale $record, Action $action): void {
                if (! static::confirm($record)) {
                    $action->halt();
                }
            });
    }

    /**
     * Run the confirmation and notify the user. Returns false when it was blocked.
     */
    public static function confirm(Sale $sale): bool
    {
        try {
            app(ProcessSaleInvoiceAction::class)->execute($sale, auth()->id());
        } catch (CreditLimitExceededException|InsufficientStockException|CashboxBalanceException|InvalidUomConversionException $e) {
            // The transaction rolled back, but the in-memory model may already say CONFIRMED
            $sale->refresh();

            Notification::make()
                ->title(__('erp.actions.confirm_sale_failed'))
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return false;
        }

        Notification::make()
            ->title(__('erp.actions.confirm_sale_success'))
            ->success()
            ->send();

        return true;
    }
}
