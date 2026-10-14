<?php

namespace Tests\Feature;

use App\Actions\Inventory\ImportItemsAction;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Models\Category;
use App\Models\Item;
use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsSpreadsheets;
use Tests\TestCase;

class ItemsImportTest extends TestCase
{
    use BuildsSpreadsheets, RefreshDatabase;

    protected MeasurementUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->unit = MeasurementUnit::create(['name' => ['ar' => 'عدد', 'en' => 'Count'], 'slug' => 'count']);
        MeasurementUnitDetail::create(['measurement_unit_id' => $this->unit->id, 'name' => 'Piece', 'unit_type' => 'numbers', 'is_default' => true, 'value' => 1]);
        Category::create(['name' => ['ar' => 'زجاج', 'en' => 'Glassware'], 'slug' => 'glassware']);
    }

    public function test_template_download(): void
    {
        Livewire::test(ListItems::class)
            ->callAction('downloadItemsTemplate')
            ->assertFileDownloaded('items-template.xlsx');
    }

    public function test_items_are_imported_and_new_categories_created(): void
    {
        $file = $this->xlsxUpload([
            ImportItemsAction::HEADERS,
            ['كوب', 'Cup', 'CUP-01', 'glassware', 'Count', '123456', 5],
            ['طبق', '', 'PLT-01', 'مطبخ', 'عدد', '', ''],
        ]);

        Livewire::test(ListItems::class)
            ->callAction('importItems', ['file' => $file])
            ->assertHasNoFormErrors()
            ->assertNotified(__('erp.import.items_imported', ['count' => 2]));

        $cup = Item::where('sku', 'CUP-01')->sole();
        $this->assertEquals('Cup', $cup->getTranslation('name', 'en'));
        $this->assertEquals('كوب', $cup->getTranslation('name', 'ar'));
        $this->assertEquals('Glassware', $cup->category->getTranslation('name', 'en'));
        $this->assertEquals($this->unit->id, $cup->measurement_unit_id);
        $this->assertEquals('123456', $cup->barcode);
        $this->assertEquals(5.0, (float) $cup->min_stock_alert);

        $plate = Item::where('sku', 'PLT-01')->sole();
        $this->assertEquals('طبق', $plate->getTranslation('name', 'en')); // falls back to the Arabic name
        $this->assertEquals('مطبخ', $plate->category->getTranslation('name', 'ar'));
        $this->assertEquals(2, Category::count());
    }

    public function test_nothing_is_saved_when_a_row_is_invalid(): void
    {
        Item::create(['category_id' => Category::first()->id, 'measurement_unit_id' => $this->unit->id, 'sku' => 'OLD-01', 'name' => ['ar' => 'قديم', 'en' => 'Old']]);

        $file = $this->xlsxUpload([
            ImportItemsAction::HEADERS,
            ['كوب', 'Cup', 'CUP-01', 'Glassware', 'Count', '', ''],
            ['', '', 'CUP-02', 'Glassware', 'Count', '', ''],       // no name
            ['طبق', 'Plate', 'CUP-01', 'Glassware', 'Count', '', ''], // duplicate in file
            ['قديم', 'Old', 'OLD-01', 'Glassware', 'Count', '', ''],  // exists already
            ['كيلو', 'Kilo', 'KG-01', 'Glassware', 'Weight', '', ''],  // unknown unit
        ]);

        Livewire::test(ListItems::class)
            ->callAction('importItems', ['file' => $file])
            ->assertNotified(__('erp.import.failed'));

        $this->assertEquals(1, Item::count());
        $this->assertEquals(1, Category::count());
    }
}
