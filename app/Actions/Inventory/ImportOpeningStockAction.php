<?php

namespace App\Actions\Inventory;

use App\Exceptions\ImportValidationException;
use App\Models\Item;
use App\Models\OpeningBalance;
use App\Support\SpreadsheetTemplate;

/**
 * Turn the opening stock template (quantities and costs in each item's default unit) into an opening balance.
 * The template lists every item; rows left without a quantity are skipped. name/unit columns are for reference only.
 */
class ImportOpeningStockAction
{
    public const HEADERS = ['sku', 'name', 'unit', 'quantity', 'cost', 'selling_price'];

    public function __construct(
        protected PostOpeningBalanceAction $postOpeningBalance
    ) {}

    public function execute(string $path, int $warehouseId, string $issueDate, int $userId, ?string $notes = null): OpeningBalance
    {
        $rows = array_filter(SpreadsheetTemplate::read($path), fn (array $row) => ($row['quantity'] ?? '') !== '');

        if ($rows === []) {
            throw new ImportValidationException([__('erp.import.empty_file')]);
        }

        $items = Item::with('measurementUnit.details')
            ->whereIn('sku', array_column($rows, 'sku'))
            ->get()
            ->keyBy(fn (Item $item) => strtolower($item->sku));

        $errors = [];
        $lines = [];

        foreach ($rows as $line => $row) {
            $item = $items[strtolower($row['sku'] ?? '')] ?? null;
            $unitDetail = $item?->measurementUnit?->details->firstWhere('is_default', true);
            $rowErrors = [];

            if (($row['sku'] ?? '') === '') {
                $rowErrors[] = __('erp.import.sku_required');
            } elseif (! $item) {
                $rowErrors[] = __('erp.import.sku_unknown', ['sku' => $row['sku']]);
            } elseif (! $unitDetail) {
                $rowErrors[] = __('erp.import.no_default_unit', ['sku' => $row['sku']]);
            }

            if (! is_numeric($row['quantity']) || (float) $row['quantity'] <= 0) {
                $rowErrors[] = __('erp.import.quantity_invalid');
            }

            if (! is_numeric($row['cost'] ?? '') || (float) $row['cost'] < 0) {
                $rowErrors[] = __('erp.import.cost_invalid');
            }

            if (($row['selling_price'] ?? '') !== '' && (! is_numeric($row['selling_price']) || (float) $row['selling_price'] < 0)) {
                $rowErrors[] = __('erp.import.not_a_number', ['field' => 'selling_price']);
            }

            foreach ($rowErrors as $error) {
                $errors[] = __('erp.import.row', ['row' => $line]).': '.$error;
            }

            if ($rowErrors === []) {
                $lines[] = [
                    'item_id' => $item->id,
                    'measurement_unit_detail_id' => $unitDetail->id,
                    'quantity' => (float) $row['quantity'],
                    'unit_cost' => (float) $row['cost'],
                    'selling_price' => ($row['selling_price'] ?? '') !== '' ? (float) $row['selling_price'] : null,
                ];
            }
        }

        if ($errors !== []) {
            throw new ImportValidationException($errors);
        }

        return $this->postOpeningBalance->execute($warehouseId, $issueDate, $lines, $userId, $notes);
    }
}
