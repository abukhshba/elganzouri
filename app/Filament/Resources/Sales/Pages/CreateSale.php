<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\Actions\ConfirmSaleAction;
use App\Filament\Resources\Sales\Pages\Concerns\HandlesSaleFormData;
use App\Filament\Resources\Sales\SalesResource;
use App\Models\Warehouse;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    use HandlesSaleFormData;

    protected static string $resource = SalesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->prepareSaleData($data);

        // Auto-assign status, dates and owner
        $data['status'] = SaleStatus::DRAFT->value;
        $data['issue_date'] = now();
        $data['user_id'] = auth()->id() ?? 1;

        if (empty($data['warehouse_id'])) {
            $data['warehouse_id'] = Warehouse::first()?->id;
        }

        // Cost and profit are recalculated in afterCreate
        $data['total_cogs'] = 0;
        $data['total_profit'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncSaleTotals($this->record);

        // Sales are confirmed on save; the paid amount becomes the first payment
        ConfirmSaleAction::confirm($this->record);
    }
}
