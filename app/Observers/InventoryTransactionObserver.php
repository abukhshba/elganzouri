<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use Exception;

class InventoryTransactionObserver
{
    /**
     * Prevent updating an existing inventory transaction ledger entry.
     */
    public function updating(InventoryTransaction $transaction): bool
    {
        throw new Exception("Immutable Ledger Error: Inventory transaction entries cannot be updated.");
    }

    /**
     * Prevent deleting an existing inventory transaction ledger entry.
     */
    public function deleting(InventoryTransaction $transaction): bool
    {
        throw new Exception("Immutable Ledger Error: Inventory transaction entries cannot be deleted.");
    }
}
