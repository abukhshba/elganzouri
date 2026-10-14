<?php

namespace Tests\Feature;

use App\Actions\Purchasing\ProcessPurchaseInvoiceAction;
use App\Actions\Sales\ProcessSaleInvoiceAction;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Filament\Resources\ItemInventories\ItemInventoryResource;
use App\Filament\Resources\ItemInventories\Pages\ListItemInventories;
use App\Filament\Resources\ItemInventories\Pages\ViewItemInventory;
use App\Filament\Resources\ItemInventories\RelationManagers\StockMovementsRelationManager;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_list_and_item_movements_add_up_to_current_stock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $unitGroup = MeasurementUnit::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $piece = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Piece', 'unit_type' => 'numbers', 'is_default' => true, 'value' => 1]);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $item = Item::create(['category_id' => $category->id, 'measurement_unit_id' => $unitGroup->id, 'sku' => 'CUP-01', 'name' => 'Cup']);
        $other = Item::create(['category_id' => $category->id, 'measurement_unit_id' => $unitGroup->id, 'sku' => 'PLT-01', 'name' => 'Plate']);

        // Purchase 100 cups and 5 plates
        $purchase = Purchase::create(['supplier_id' => Supplier::create(['name' => 'Vendor'])->id, 'warehouse_id' => $warehouse->id, 'status' => PurchaseStatus::DRAFT, 'total_amount' => 1050.0, 'issue_date' => now(), 'user_id' => $user->id]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'item_id' => $item->id, 'measurement_unit_detail_id' => $piece->id, 'quantity' => 100, 'unit_price' => 10, 'conversion_factor' => 1, 'base_quantity' => 100, 'base_unit_cost' => 10, 'line_total' => 1000]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'item_id' => $other->id, 'measurement_unit_detail_id' => $piece->id, 'quantity' => 5, 'unit_price' => 10, 'conversion_factor' => 1, 'base_quantity' => 5, 'base_unit_cost' => 10, 'line_total' => 50]);
        app(ProcessPurchaseInvoiceAction::class)->execute($purchase, $user->id);

        // Sell 30 cups
        $sale = Sale::create(['customer_id' => Customer::create(['name' => 'Customer'])->id, 'warehouse_id' => $warehouse->id, 'status' => SaleStatus::DRAFT, 'total_amount' => 600.0, 'issue_date' => now(), 'user_id' => $user->id]);
        SaleItem::create(['sale_id' => $sale->id, 'item_id' => $item->id, 'measurement_unit_detail_id' => $piece->id, 'quantity' => 30, 'unit_price' => 20, 'conversion_factor' => 1, 'base_quantity' => 30, 'line_total' => 600]);
        app(ProcessSaleInvoiceAction::class)->execute($sale, $user->id);

        $cups = ItemInventory::where('item_id', $item->id)->sole();
        $plates = ItemInventory::where('item_id', $other->id)->sole();

        Livewire::test(ListItemInventories::class)
            ->assertCanSeeTableRecords([$cups, $plates])
            ->assertTableColumnStateSet('current_quantity', 70, $cups)
            ->assertTableColumnStateSet('base_unit', 'Piece', $cups)
            ->searchTable('Plate')
            ->assertCanSeeTableRecords([$plates])
            ->assertCanNotSeeTableRecords([$cups]);

        // Item and warehouse filters
        $branch = Warehouse::create(['name' => 'Branch WH', 'code' => 'WH-B']);
        $branchCups = ItemInventory::create(['item_id' => $item->id, 'warehouse_id' => $branch->id, 'current_quantity' => 5]);

        Livewire::test(ListItemInventories::class)
            ->filterTable('item_id', [$item->id])
            ->assertCanSeeTableRecords([$cups, $branchCups])
            ->assertCanNotSeeTableRecords([$plates])
            ->filterTable('warehouse_id', [$branch->id])
            ->assertCanSeeTableRecords([$branchCups])
            ->assertCanNotSeeTableRecords([$cups, $plates]);

        $movements = InventoryTransaction::where('item_inventory_id', $cups->id)->orderBy('id')->get();

        Livewire::test(StockMovementsRelationManager::class, ['ownerRecord' => $cups, 'pageClass' => ViewItemInventory::class])
            ->assertCanSeeTableRecords($movements, inOrder: true)
            ->assertCanNotSeeTableRecords(InventoryTransaction::where('item_inventory_id', $plates->id)->get())
            ->assertTableColumnFormattedStateSet('quantity', '+100.00', $movements[0])
            ->assertTableColumnFormattedStateSet('quantity', '−30.00', $movements[1])
            ->assertTableColumnStateSet('balance_after', 100, $movements[0])
            ->assertTableColumnStateSet('balance_after', 70, $movements[1])
            ->assertTableColumnStateSet('source', $purchase->purchase_number, $movements[0])
            ->assertTableColumnStateSet('source', $sale->invoice_number, $movements[1]);

        $this->get(ItemInventoryResource::getUrl('index'))->assertOk()->assertSee('Cup');
        $this->get(ItemInventoryResource::getUrl('view', ['record' => $cups]))->assertOk()->assertSee('Cup');
    }
}
