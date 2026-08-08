<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Services\UomConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UomConversionTest extends TestCase
{
    use RefreshDatabase;

    protected UomConversionService $conversionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->conversionService = app(UomConversionService::class);
    }

    public function test_global_conversion_ratio_resolves_correctly_for_weight(): void
    {
        $category = Category::first();
        $gram = Unit::where('short_name', 'g')->first();
        $kg = Unit::where('short_name', 'kg')->first();

        $item = Item::create([
            'sku' => 'TEST-WEIGHT-ITEM',
            'name' => ['en' => 'Test Weight Item', 'ar' => 'صنف وزن للاختبار'],
            'category_id' => $category->id,
            'base_unit_id' => $gram->id,
            'min_stock_alert' => 0.0,
            'is_active' => true,
        ]);

        // 5 Kg to Grams = 5000 Grams
        $baseQty = $this->conversionService->convertToBaseQuantity($item, $kg, 5.0);
        $this->assertEquals(5000.0, $baseQty);

        // Unit cost per Gram from EGP 500 per Kg = EGP 0.5 per Gram
        $baseCost = $this->conversionService->calculateBaseUnitCost($item, $kg, 500.0);
        $this->assertEquals(0.5, $baseCost);
    }

    public function test_global_conversion_ratio_resolves_correctly_for_length(): void
    {
        $category = Category::first();
        $cm = Unit::where('short_name', 'cm')->first();
        $meter = Unit::where('short_name', 'm')->first();

        $item = Item::create([
            'sku' => 'TEST-LENGTH-ITEM',
            'name' => ['en' => 'Test Length Item', 'ar' => 'صنف طول للاختبار'],
            'category_id' => $category->id,
            'base_unit_id' => $cm->id,
            'min_stock_alert' => 0.0,
            'is_active' => true,
        ]);

        // 2.5 Meters to Centimeters = 250 Centimeters
        $baseQty = $this->conversionService->convertToBaseQuantity($item, $meter, 2.5);
        $this->assertEquals(250.0, $baseQty);
    }

    public function test_item_specific_conversion_ratios_for_cartons(): void
    {
        $carton = Unit::where('short_name', 'ctn')->first();

        $cup = Item::where('sku', 'CUP-CERAMIC-01')->first();
        $plate = Item::where('sku', 'PLATE-DINNER-01')->first();
        $spoons = Item::where('sku', 'SPOONS-STAINLESS-01')->first();

        // Ceramic Cup: 2 Cartons * 72 Pcs = 144 Pieces
        $cupBaseQty = $this->conversionService->convertToBaseQuantity($cup, $carton, 2.0);
        $this->assertEquals(144.0, $cupBaseQty);

        // Dinner Plate: 2 Cartons * 24 Pcs = 48 Pieces
        $plateBaseQty = $this->conversionService->convertToBaseQuantity($plate, $carton, 2.0);
        $this->assertEquals(48.0, $plateBaseQty);

        // Spoons: 3 Cartons * 144 Pcs = 432 Pieces
        $spoonsBaseQty = $this->conversionService->convertToBaseQuantity($spoons, $carton, 3.0);
        $this->assertEquals(432.0, $spoonsBaseQty);
    }

    public function test_unconfigured_custom_unit_throws_exception(): void
    {
        $category = Category::first();
        $pc = Unit::where('short_name', 'pc')->first();
        $carton = Unit::where('short_name', 'ctn')->first();

        // Create item without item_units override for Carton
        $newItem = Item::create([
            'sku' => 'TEST-NO-RATIO-ITEM',
            'name' => ['en' => 'Test Item', 'ar' => 'صنف تجريبي'],
            'category_id' => $category->id,
            'base_unit_id' => $pc->id,
            'min_stock_alert' => 0.0,
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->conversionService->convertToBaseQuantity($newItem, $carton, 1.0);
    }
}
