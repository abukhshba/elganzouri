<?php

namespace App\Actions\Sales;

use App\Models\Sale;
use App\Models\Setting;

class GenerateThermalReceiptAction
{
    /**
     * Generate 80mm thermal receipt payload string for sales invoice.
     */
    public function execute(Sale $sale): string
    {
        $sale->loadMissing(['customer', 'warehouse', 'items.item', 'items.unit']);

        $companyName = Setting::get('company_name', 'Household Products ERP');
        $phone = Setting::get('company_phone', '');
        $taxNumber = Setting::get('company_tax_number', '');

        $lines = [];
        $lines[] = "========================================";
        $lines[] = str_pad($companyName, 40, " ", STR_PAD_BOTH);
        if ($phone) {
            $lines[] = str_pad("Tel: {$phone}", 40, " ", STR_PAD_BOTH);
        }
        if ($taxNumber) {
            $lines[] = str_pad("Tax ID: {$taxNumber}", 40, " ", STR_PAD_BOTH);
        }
        $lines[] = "========================================";
        $lines[] = "Invoice #: {$sale->invoice_number}";
        $lines[] = "Date: " . $sale->issue_date->format('Y-m-d H:i');
        $lines[] = "Customer: {$sale->customer->name}";
        $lines[] = "Warehouse: {$sale->warehouse->name}";
        $lines[] = "----------------------------------------";
        $lines[] = sprintf("%-18s %4s %8s %8s", "Item", "Qty", "Price", "Total");
        $lines[] = "----------------------------------------";

        foreach ($sale->items as $item) {
            $itemName = mb_strimwidth($item->item->name, 0, 18, "..");
            $qty = number_format((float) $item->quantity, 2);
            $price = number_format((float) $item->unit_price, 2);
            $total = number_format((float) $item->line_total, 2);

            $lines[] = sprintf("%-18s %4s %8s %8s", $itemName, $qty, $price, $total);
        }

        $lines[] = "----------------------------------------";
        $lines[] = sprintf("%-28s %11s", "Net Total:", number_format((float) $sale->total_amount, 2) . " EGP");
        $lines[] = sprintf("%-28s %11s", "Paid Cash:", number_format((float) $sale->paid_amount, 2) . " EGP");
        $lines[] = sprintf("%-28s %11s", "Due Debt:", number_format((float) $sale->due_amount, 2) . " EGP");
        $lines[] = "========================================";
        $lines[] = str_pad("Thank you for your business!", 40, " ", STR_PAD_BOTH);
        $lines[] = "========================================";

        return implode("\n", $lines);
    }
}
