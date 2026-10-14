<?php

namespace Tests\Feature;

use App\Actions\Inventory\ImportItemsAction;
use App\Actions\Inventory\ImportOpeningStockAction;
use App\Enums\InventoryTransactionType;
use App\Exceptions\ImportValidationException;
use App\Filament\Resources\OpeningBalances\OpeningBalanceResource;
use App\Filament\Resources\OpeningBalances\Pages\CreateOpeningBalance;
use App\Filament\Resources\OpeningBalances\Pages\ListOpeningBalances;
use App\Models\CashboxTransaction;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\ItemPrice;
use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;
use App\Models\OpeningBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\SpreadsheetTemplate;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsSpreadsheets;
use Tests\TestCase;

class OpeningBalanceTest extends TestCase
{
    use BuildsSpreadsheets, RefreshDatabase;

    protected Warehouse $warehouse;

    protected MeasurementUnitDetail $piece;

    protected MeasurementUnitDetail $box;

    protected Item $cup;

    protected Item $plate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $unit = MeasurementUnit::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $this->piece = MeasurementUnitDetail::create(['measurement_unit_id' => $unit->id, 'name' => 'Piece', 'unit_type' => 'numbers', 'is_default' => true, 'value' => 1]);
        $this->box = MeasurementUnitDetail::create(['measurement_unit_id' => $unit->id, 'name' => 'Box', 'unit_type' => 'numbers', 'is_default' => false, 'value' => 12]);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);
        $this->warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-M']);
        $this->cup = Item::create(['category_id' => $category->id, 'measurement_unit_id' => $unit->id, 'sku' => 'CUP-01', 'name' => 'Cup']);
        $this->plate = Item::create(['category_id' => $category->id, 'measurement_unit_id' => $unit->id, 'sku' => 'PLT-01', 'name' => 'Plate']);
    }

    protected function stock(Item $item, ?Warehouse $warehouse = null): ?ItemInventory
    {
        return ItemInventory::where('item_id', $item->id)->where('warehouse_id', ($warehouse ?? $this->warehouse)->id)->first();
    }

    protected function createFromForm(array $lines, ?Warehouse $warehouse = null)
    {
        $undoRepeaterFake = Repeater::fake();

        $test = Livewire::test(CreateOpeningBalance::class)
            ->fillForm([
                'warehouse_id' => ($warehouse ?? $this->warehouse)->id,
                'issue_date' => now()->toDateString(),
                'lines' => $lines,
            ])
            ->call('create');

        $undoRepeaterFake();

        return $test;
    }

    public function test_form_posts_opening_stock_without_money(): void
    {
        $this->createFromForm([
            ['item_id' => $this->cup->id, 'measurement_unit_detail_id' => $this->box->id, 'quantity' => 3, 'unit_cost' => 120, 'selling_price' => 200],
            ['item_id' => $this->plate->id, 'measurement_unit_detail_id' => $this->piece->id, 'quantity' => 10, 'unit_cost' => 5],
        ])
            ->assertHasNoFormErrors()
            ->assertRedirect(OpeningBalanceResource::getUrl('view', ['record' => OpeningBalance::first()]));

        $opening = OpeningBalance::with('items')->sole();
        $this->assertNotEmpty($opening->opening_number);
        $this->assertEquals(410.0, (float) $opening->total_value); // 3 x 120 + 10 x 5
        $this->assertCount(2, $opening->items);

        $cups = $this->stock($this->cup);
        $this->assertEquals(36.0, (float) $cups->current_quantity); // 3 boxes x 12
        $this->assertEquals(10.0, (float) $cups->average_cost);     // 120 / 12
        $this->assertEquals(10.0, (float) $this->stock($this->plate)->current_quantity);

        $movement = InventoryTransaction::where('item_id', $this->cup->id)->sole();
        $this->assertEquals(InventoryTransactionType::OPENING, $movement->transaction_type);
        $this->assertEquals(OpeningBalance::class, $movement->reference_type);

        $price = ItemPrice::where('item_id', $this->cup->id)->sole();
        $this->assertEquals(200.0, (float) $price->price);
        $this->assertEquals($this->box->id, $price->measurement_unit_detail_id);
        $this->assertTrue($price->priceList->is_default);
        $this->assertEquals(0, ItemPrice::where('item_id', $this->plate->id)->count());

        $this->assertEquals(0, CashboxTransaction::count());
        $this->get(OpeningBalanceResource::getUrl('view', ['record' => $opening]))->assertOk()->assertSee('CUP-01');
    }

    public function test_an_item_gets_only_one_opening_balance_per_warehouse(): void
    {
        $line = ['item_id' => $this->cup->id, 'measurement_unit_detail_id' => $this->piece->id, 'quantity' => 10, 'unit_cost' => 5];

        $this->createFromForm([$line])->assertHasNoFormErrors();

        $this->createFromForm([$line])->assertNotified(__('erp.import.opening_failed'));
        $this->assertEquals(1, OpeningBalance::count());
        $this->assertEquals(10.0, (float) $this->stock($this->cup)->current_quantity);

        // Another warehouse is still allowed
        $branch = Warehouse::create(['name' => 'Branch WH', 'code' => 'WH-B']);
        $this->createFromForm([$line], $branch)->assertHasNoFormErrors();
        $this->assertEquals(10.0, (float) $this->stock($this->cup, $branch)->current_quantity);
    }

    public function test_template_download_lists_every_item(): void
    {
        $test = Livewire::test(ListOpeningBalances::class)
            ->callAction('downloadOpeningTemplate')
            ->assertFileDownloaded('opening-stock-template.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'tpl');
        file_put_contents($path, base64_decode($test->effects['download']['content']));
        $rows = array_values(SpreadsheetTemplate::read($path));

        $this->assertEquals(ImportOpeningStockAction::HEADERS, array_keys($rows[0]));
        $this->assertEquals(['CUP-01', 'PLT-01'], array_column($rows, 'sku'));
        $this->assertEquals('Piece', $rows[0]['unit']);
        $this->assertEquals('', $rows[0]['quantity']);
    }

    public function test_sheet_upload_creates_opening_balance_for_chosen_warehouse(): void
    {
        $branch = Warehouse::create(['name' => 'Branch WH', 'code' => 'WH-B']);

        $file = $this->xlsxUpload([
            ImportOpeningStockAction::HEADERS,
            ['CUP-01', 'Cup', 'Piece', 50, 8, 15],
            ['PLT-01', 'Plate', 'Piece', '', '', ''], // no quantity: skipped
        ]);

        Livewire::test(ListOpeningBalances::class)
            ->callAction('importOpeningStock', ['warehouse_id' => $branch->id, 'issue_date' => now()->toDateString(), 'file' => $file])
            ->assertHasNoFormErrors()
            ->assertNotified(__('erp.import.opening_imported', ['count' => 1]));

        $opening = OpeningBalance::sole();
        $this->assertEquals($branch->id, $opening->warehouse_id);
        $this->assertEquals(50.0, (float) $this->stock($this->cup, $branch)->current_quantity);
        $this->assertEquals(8.0, (float) $this->stock($this->cup, $branch)->average_cost);
        $this->assertNull($this->stock($this->plate, $branch));
        $this->assertEquals(15.0, (float) ItemPrice::where('item_id', $this->cup->id)->value('price'));
    }

    public function test_sheet_errors_are_reported_and_nothing_is_saved(): void
    {
        $path = $this->xlsxPath([
            ImportOpeningStockAction::HEADERS,
            ['CUP-01', '', '', 50, 8, ''],
            ['NOPE-99', '', '', 5, 1, ''],     // unknown SKU
            ['PLT-01', '', '', -2, 1, ''],     // bad quantity
            ['PLT-01', '', '', 3, 'abc', ''],  // bad cost
        ]);

        try {
            app(ImportOpeningStockAction::class)->execute($path, $this->warehouse->id, now()->toDateString(), auth()->id());
            $this->fail('Expected ImportValidationException');
        } catch (ImportValidationException $e) {
            $this->assertCount(3, $e->errors);
            $this->assertStringContainsString('NOPE-99', $e->errors[0]);
            $this->assertStringStartsWith(__('erp.import.row', ['row' => 3]), $e->errors[0]);
        }

        $this->assertEquals(0, OpeningBalance::count());
        $this->assertEquals(0, InventoryTransaction::count());
    }

    public function test_items_import_errors_name_the_rows(): void
    {
        $path = $this->xlsxPath([
            ImportItemsAction::HEADERS,
            ['', '', 'X-1', 'Glassware', 'packaging', '', ''],
            ['كوب', '', 'CUP-01', 'Glassware', 'packaging', '', ''],
        ]);

        try {
            app(ImportItemsAction::class)->execute($path);
            $this->fail('Expected ImportValidationException');
        } catch (ImportValidationException $e) {
            $this->assertEquals([
                __('erp.import.row', ['row' => 2]).': '.__('erp.import.name_required'),
                __('erp.import.row', ['row' => 3]).': '.__('erp.import.sku_exists', ['sku' => 'CUP-01']),
            ], $e->errors);
        }
    }
}
