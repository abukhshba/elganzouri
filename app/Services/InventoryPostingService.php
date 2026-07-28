<?php

namespace App\Services;

use App\Actions\Inventory\CalculateWacAction;
use App\Enums\InventoryTransactionType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemInventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryPostingService
{
    public function __construct(
        protected CalculateWacAction $calculateWacAction
    ) {}

    /**
     * Atomically post an inventory ledger transaction and update warehouse stock snapshot.
     */
    public function post(
        int $itemId,
        int $warehouseId,
        InventoryTransactionType $type,
        float $quantityBase,
        float $unitCostBase,
        Model $referenceModel,
        int $userId,
        ?string $notes = null
    ): InventoryTransaction {
        if ($quantityBase <= 0) {
            throw new \InvalidArgumentException("Base transaction quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($itemId, $warehouseId, $type, $quantityBase, $unitCostBase, $referenceModel, $userId, $notes) {
            $item = Item::findOrFail($itemId);

            // Pessimistic row locking on target warehouse inventory snapshot
            $inventory = ItemInventory::where('item_id', $itemId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = ItemInventory::create([
                    'item_id' => $itemId,
                    'warehouse_id' => $warehouseId,
                    'current_quantity' => 0.0000,
                    'reserved_quantity' => 0.0000,
                    'average_cost' => 0.0000,
                    'stock_value' => 0.0000,
                    'minimum_quantity' => $item->min_stock_alert,
                ]);
            }

            $currentQty = (float) $inventory->current_quantity;
            $currentWac = (float) $inventory->average_cost;

            // Validate available stock for outflows
            if ($type->isOutflow()) {
                $availableQty = $inventory->available_quantity;

                if ($quantityBase > $availableQty) {
                    throw new InsufficientStockException(
                        "Insufficient stock for Item '{$item->name}' (SKU: {$item->sku}) in Warehouse ID {$warehouseId}. Requested: {$quantityBase}, Available: {$availableQty}."
                    );
                }

                $newQty = round($currentQty - $quantityBase, 4);
                $newWac = $currentWac; // Cost remains unchanged on outflow
                $appliedUnitCost = $currentWac; // Outflow consumed at current WAC
            } else {
                // Stock Inflow
                $newQty = round($currentQty + $quantityBase, 4);
                $newWac = $this->calculateWacAction->execute($currentQty, $currentWac, $quantityBase, $unitCostBase);
                $appliedUnitCost = $unitCostBase;
            }

            $newStockValue = round($newQty * $newWac, 4);

            // Write Immutable Ledger Record
            $transaction = InventoryTransaction::create([
                'item_inventory_id' => $inventory->id,
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'base_unit_id' => $item->base_unit_id,
                'transaction_type' => $type,
                'quantity' => $quantityBase,
                'unit_cost' => $appliedUnitCost,
                'total_cost' => round($quantityBase * $appliedUnitCost, 4),
                'balance_after' => $newQty,
                'average_cost_after' => $newWac,
                'reference_type' => get_class($referenceModel),
                'reference_id' => $referenceModel->getKey(),
                'performed_by' => $userId,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            // Update Materialized Snapshot
            $inventory->update([
                'current_quantity' => $newQty,
                'average_cost' => $newWac,
                'stock_value' => $newStockValue,
                'last_purchase_price' => $type === InventoryTransactionType::IN ? $unitCostBase : $inventory->last_purchase_price,
                'last_transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }
}
