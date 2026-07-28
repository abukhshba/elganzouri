<?php

namespace App\Filament\Widgets;

use App\Services\ReportsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $reportsService = app(ReportsService::class);

        $inventory = $reportsService->getInventoryValuationReport();
        $profitability = $reportsService->getProfitabilityReport();
        $arAp = $reportsService->getArApBalancesReport();
        $cashbox = $reportsService->getCashboxSummaryReport();

        return [
            Stat::make('Stock Valuation', number_format($inventory['total_stock_value'], 2) . ' EGP')
                ->description("{$inventory['total_items']} items in stock")
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('primary'),

            Stat::make('Total Sales Revenue', number_format($profitability['total_sales'], 2) . ' EGP')
                ->description("Gross Margin: {$profitability['gross_margin_percentage']}%")
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Net Gross Profit', number_format($profitability['total_gross_profit'], 2) . ' EGP')
                ->description('Real-time COGS profit')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Cash in Drawers', number_format($cashbox['total_cash_in_drawers'], 2) . ' EGP')
                ->description("{$cashbox['active_registers_count']} active registers")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Customer AR Debt', number_format($arAp['total_customer_ar_debt'], 2) . ' EGP')
                ->description('Outstanding receivables')
                ->descriptionIcon('heroicon-m-users')
                ->color('danger'),

            Stat::make('Supplier AP Debt', number_format($arAp['total_supplier_ap_debt'], 2) . ' EGP')
                ->description('Outstanding payables')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
        ];
    }
}
