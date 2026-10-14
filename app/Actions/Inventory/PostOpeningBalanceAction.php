<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryTransactionType;
use App\Exceptions\ImportValidationException;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\OpeningBalance;
use App\Models\PriceList;
use App\Services\InventoryPostingService;
use App\Services\UomService;
use Illuminate\Support\Facades\DB;

/**
 * Record go-live stock for one warehouse: OPENING inventory movements at the given cost, plus optional selling prices.
 * An item can only get an opening balance in a warehouse that has no movements for it yet.
 */
class PostOpeningBalanceAction
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService,
        protected UomService $uomService
    ) {}

    /**
     * @param  list<array{item_id: int, measurement_unit_detail_id: int, quantity: float, unit_cost: float, selling_price?: float|null}>  $lines
     */
    public function execute(int $warehouseId, string $issueDate, array $lines, int $userId, ?string $notes = null): OpeningBalance
    {
        $this->guardAgainstExistingStock($warehouseId, $lines);

        return DB::transaction(function () use ($warehouseId, $issueDate, $lines, $userId, $notes) {
            $opening = OpeningBalance::create([
                'warehouse_id' => $warehouseId,
                'issue_date' => $issueDate,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            $totalValue = 0.0;

            foreach ($lines as $line) {
                $unitDetailId = (int) $line['measurement_unit_detail_id'];
                $quantity = (float) $line['quantity'];
                $unitCost = (float) $line['unit_cost'];
                $lineTotal = round($quantity * $unitCost, 4);
                $baseQuantity = $this->uomService->convertQuantityToBaseUnit($unitDetailId, $quantity);
                $baseUnitCost = $this->uomService->convertPriceToBaseUnit($unitDetailId, $unitCost);
                $sellingPrice = isset($line['selling_price']) && $line['selling_price'] !== '' ? (float) $line['selling_price'] : null;

                $opening->items()->create([
                    'item_id' => $line['item_id'],
                    'measurement_unit_detail_id' => $unitDetailId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'selling_price' => $sellingPrice,
                    'conversion_factor' => $this->uomService->getConversionFactor($unitDetailId),
                    'base_quantity' => $baseQuantity,
                    'base_unit_cost' => $baseUnitCost,
                    'line_total' => $lineTotal,
                ]);

                $this->inventoryPostingService->post(
                    itemId: $line['item_id'],
                    warehouseId: $warehouseId,
                    type: InventoryTransactionType::OPENING,
                    quantityBase: $baseQuantity,
                    unitCostBase: $baseUnitCost,
                    referenceModel: $opening,
                    userId: $userId,
                    notes: "Opening Balance #{$opening->opening_number}"
                );

                if ($sellingPrice !== null) {
                    ItemPrice::updateOrCreate(
                        ['price_list_id' => $this->defaultPriceList()->id, 'item_id' => $line['item_id'], 'measurement_unit_detail_id' => $unitDetailId],
                        ['price' => $sellingPrice, 'is_active' => true]
                    );
                }

                $totalValue += $lineTotal;
            }

            $opening->update(['total_value' => round($totalValue, 4)]);

            return $opening;
        });
    }

    protected function guardAgainstExistingStock(int $warehouseId, array $lines): void
    {
        $itemIds = array_unique(array_map(fn ($line) => (int) $line['item_id'], $lines));

        $blocked = InventoryTransaction::where('warehouse_id', $warehouseId)
            ->whereIn('item_id', $itemIds)
            ->distinct()
            ->pluck('item_id');

        if ($blocked->isNotEmpty()) {
            throw new ImportValidationException(
                Item::whereIn('id', $blocked)->get()
                    ->map(fn (Item $item) => __('erp.import.already_has_stock', ['item' => $item->name, 'sku' => $item->sku]))
                    ->all()
            );
        }
    }

    protected function defaultPriceList(): PriceList
    {
        return PriceList::where('is_default', true)->first()
            ?? PriceList::firstOrCreate(
                ['code' => 'DEFAULT'],
                ['name' => ['ar' => 'قائمة الأسعار الأساسية', 'en' => 'Default Price List'], 'is_default' => true, 'is_active' => true]
            );
    }
}
