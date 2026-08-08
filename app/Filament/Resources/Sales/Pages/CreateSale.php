<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\CashboxTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\SalesResource;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Unit;
use App\Services\UomConversionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateSale extends CreateRecord
{
    protected static string $resource = SalesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Inline New Customer Creation
        if (! empty($data['is_new_customer']) && ! empty($data['new_customer_name'])) {
            $customer = Customer::create([
                'name' => $data['new_customer_name'],
                'phone' => $data['new_customer_phone'] ?? null,
                'is_active' => true,
            ]);
            $data['customer_id'] = $customer->id;
        }

        // 2. Auto-assign Status, Document Number & Dates
        $data['status'] = SaleStatus::DRAFT->value;
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = 'INV-'.strtoupper(bin2hex(random_bytes(4)));
        }
        $data['issue_date'] = now();
        $data['user_id'] = auth()->id() ?? 1;

        // 3. Compute Line Items Totals & Base Quantities
        $items = $data['items'] ?? [];
        $calculatedTotal = 0.0;
        $totalCogs = 0.0;
        $conversionService = app(UomConversionService::class);

        foreach ($items as &$item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $item['line_total'] = $qty * $price;
            $calculatedTotal += $item['line_total'];

            // Compute UOM Conversion & COGS
            $itemModel = ! empty($item['item_id']) ? Item::find($item['item_id']) : null;
            $unitModel = ! empty($item['unit_id']) ? Unit::find($item['unit_id']) : null;

            if ($itemModel && $unitModel) {
                $factor = $conversionService->resolveConversionFactor($itemModel, $unitModel);
            } else {
                $factor = 1.0;
            }

            $item['conversion_factor'] = $factor;
            $item['base_quantity'] = $qty * $factor;

            $avgCost = 0.0;
            if ($itemModel && ! empty($item['warehouse_id'])) {
                $avgCost = (float) ($itemModel->inventories()->where('warehouse_id', $item['warehouse_id'])->value('average_cost') ?? 0.0);
            }
            $item['base_unit_cost'] = $avgCost;
            $item['line_cogs'] = $item['base_quantity'] * $avgCost;
            $item['line_profit'] = $item['line_total'] - $item['line_cogs'];
            $totalCogs += $item['line_cogs'];
        }
        $data['items'] = $items;
        $data['total_amount'] = $calculatedTotal;
        $data['total_cogs'] = $totalCogs;
        $data['total_profit'] = $calculatedTotal - $totalCogs;

        // 4. Payment & Paid Amount Logic
        $isPaid = ! empty($data['is_paid']);
        $paidAmount = $isPaid ? $calculatedTotal : (float) ($data['paid_amount'] ?? 0);

        if ($paidAmount > $calculatedTotal) {
            throw ValidationException::withMessages([
                'paid_amount' => 'المبلغ المدفوع لا يمكن أن يكون أكبر من إجمالي الفاتورة.',
            ]);
        }

        $dueAmount = max(0, $calculatedTotal - $paidAmount);
        $data['paid_amount'] = $paidAmount;
        $data['due_amount'] = $dueAmount;

        if ($paidAmount >= $calculatedTotal && $calculatedTotal > 0) {
            $data['payment_status'] = PaymentStatus::PAID->value;
        } elseif ($paidAmount > 0) {
            $data['payment_status'] = PaymentStatus::PARTIAL->value;
        } else {
            $data['payment_status'] = PaymentStatus::UNPAID->value;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Sale $record */
        $record = $this->record;

        // Create CashboxTransaction if paid amount > 0 and cashbox is assigned
        if ((float) $record->paid_amount > 0 && $record->cashbox_id) {
            $cashbox = Cashbox::find($record->cashbox_id);

            if ($cashbox) {
                $balanceBefore = (float) $cashbox->current_balance;
                $amount = (float) $record->paid_amount;
                $balanceAfter = $balanceBefore + $amount;

                CashboxTransaction::create([
                    'cashbox_id' => $cashbox->id,
                    'transaction_type' => CashboxTransactionType::IN,
                    'reference_type' => Sale::class,
                    'reference_id' => $record->id,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'user_id' => auth()->id() ?? 1,
                    'description' => "دفعة فاتورة مبيعات رقم #{$record->invoice_number}",
                    'created_at' => now(),
                ]);

                $cashbox->update(['current_balance' => $balanceAfter]);
            }
        }
    }
}
