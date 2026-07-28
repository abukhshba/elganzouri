<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\ItemInventory;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class ReportsService
{
    /**
     * Calculate Inventory Valuation Summary Report.
     */
    public function getInventoryValuationReport(?int $warehouseId = null): array
    {
        $query = ItemInventory::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $totalItems = $query->count();
        $totalQuantity = (float) $query->sum('current_quantity');
        $totalStockValue = (float) $query->sum('stock_value');
        $lowStockCount = (clone $query)->lowStock()->count();

        return [
            'total_items' => $totalItems,
            'total_quantity' => round($totalQuantity, 4),
            'total_stock_value' => round($totalStockValue, 4),
            'low_stock_count' => $lowStockCount,
        ];
    }

    /**
     * Calculate Sales Revenue & Profitability Summary Report.
     */
    public function getProfitabilityReport(?string $fromDate = null, ?string $toDate = null): array
    {
        $query = Sale::where('status', SaleStatus::CONFIRMED);

        if ($fromDate) {
            $query->whereDate('issue_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('issue_date', '<=', $toDate);
        }

        $totalSales = (float) $query->sum('total_amount');
        $totalPaid = (float) $query->sum('paid_amount');
        $totalDue = (float) $query->sum('due_amount');
        $totalCogs = (float) $query->sum('total_cogs');
        $totalGrossProfit = (float) $query->sum('total_profit');

        $grossMargin = $totalSales > 0 ? round(($totalGrossProfit / $totalSales) * 100, 2) : 0.0;

        return [
            'total_sales' => round($totalSales, 4),
            'total_paid' => round($totalPaid, 4),
            'total_due' => round($totalDue, 4),
            'total_cogs' => round($totalCogs, 4),
            'total_gross_profit' => round($totalGrossProfit, 4),
            'gross_margin_percentage' => $grossMargin,
        ];
    }

    /**
     * Calculate Customer AR and Supplier AP Debt Summaries.
     */
    public function getArApBalancesReport(): array
    {
        $totalCustomerArDebt = (float) Customer::where('is_active', true)->sum('balance');
        $totalSupplierApDebt = (float) Supplier::where('is_active', true)->sum('balance');

        return [
            'total_customer_ar_debt' => round($totalCustomerArDebt, 4),
            'total_supplier_ap_debt' => round($totalSupplierApDebt, 4),
            'active_customers_count' => Customer::where('is_active', true)->count(),
            'active_suppliers_count' => Supplier::where('is_active', true)->count(),
        ];
    }

    /**
     * Calculate Cashbox Registers Real-time Cash Summary.
     */
    public function getCashboxSummaryReport(): array
    {
        $totalCashInDrawers = (float) Cashbox::where('is_active', true)->sum('current_balance');
        $activeRegistersCount = Cashbox::where('is_active', true)->count();

        return [
            'total_cash_in_drawers' => round($totalCashInDrawers, 4),
            'active_registers_count' => $activeRegistersCount,
        ];
    }
}
