<?php

namespace App\Filament\Resources\Purchases\Actions;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Enums\PurchaseStatus;
use App\Exceptions\CashboxBalanceException;
use App\Exceptions\InvalidUomConversionException;
use App\Models\Purchase;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ConfirmPurchaseAction
{
    public static function make(): Action
    {
        return Action::make('confirm')
            ->label(__('erp.actions.confirm'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('erp.actions.confirm_purchase_heading'))
            ->modalDescription(__('erp.actions.confirm_purchase_description'))
            ->visible(fn (Purchase $record): bool => $record->status === PurchaseStatus::DRAFT)
            ->action(function (Purchase $record, Action $action): void {
                if (! static::confirm($record)) {
                    $action->halt();
                }
            });
    }

    /**
     * Run the confirmation and notify the user. Returns false when it was blocked.
     */
    public static function confirm(Purchase $purchase): bool
    {
        try {
            app(ProcessPurchaseInvoiceAction::class)->execute($purchase, auth()->id());
        } catch (CashboxBalanceException|InvalidUomConversionException $e) {
            // The transaction rolled back, but the in-memory model may already say CONFIRMED
            $purchase->refresh();

            Notification::make()
                ->title(__('erp.actions.confirm_purchase_failed'))
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return false;
        }

        Notification::make()
            ->title(__('erp.actions.confirm_purchase_success'))
            ->success()
            ->send();

        return true;
    }
}
