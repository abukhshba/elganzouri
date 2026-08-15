<?php

namespace Tests\Feature;

use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;

use App\Models\Category;
use App\Models\Item;
use App\Models\PriceList;
use App\Services\UomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone3Test extends TestCase
{
    use RefreshDatabase;

    public function test_item_creation_with_base_unit_and_multi_uom_matrix(): void
    {
        $unitGroup = MeasurementUnit::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $piece = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Piece', 'unit_type' => 'numbers', 'is_default' => true, 'value' => 1]);
        $carton = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Carton', 'unit_type' => 'numbers', 'is_default' => false, 'value' => 72]);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);

        $item = Item::create([
            'category_id' => $category->id,
            'measurement_unit_id' => $unitGroup->id,
            'sku' => 'ITEM-CUP-101',
            'name' => 'Luminarc Glass Cup',
        ]);

        $this->assertEquals($unitGroup->id, $item->measurementUnit->id);
    }

    public function test_uom_service_quantity_and_price_conversions(): void
    {
        $unitGroup = MeasurementUnit::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $piece = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Piece', 'unit_type' => 'numbers', 'is_default' => true, 'value' => 1]);
        $pack = MeasurementUnitDetail::create(['measurement_unit_id' => $unitGroup->id, 'name' => 'Pack', 'unit_type' => 'numbers', 'is_default' => false, 'value' => 6]);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);

        $item = Item::create([
            'category_id' => $category->id,
            'measurement_unit_id' => $unitGroup->id,
            'sku' => 'ITEM-CUP-102',
            'name' => 'Test Cup',
        ]);

        $service = app(UomService::class);

        // 10 Packs * 6 = 60 Pieces
        $baseQty = $service->convertQuantityToBaseUnit($pack->id, 10.0);
        $this->assertEquals(60.0, $baseQty);

        // Price $90 per Pack / 6 = $15 per Piece
        $basePrice = $service->convertPriceToBaseUnit($pack->id, 90.0);
        $this->assertEquals(15.0, $basePrice);

        // Base unit factor is strictly 1.0
        $baseQtySameUnit = $service->convertQuantityToBaseUnit($piece->id, 5.0);
        $this->assertEquals(5.0, $baseQtySameUnit);
    }

    public function test_price_lists_creation(): void
    {
        $list = PriceList::create([
            'name' => 'Wholesale Price List',
            'code' => 'WHOLESALE',
            'currency' => 'EGP',
        ]);

        $this->assertDatabaseHas('price_lists', ['code' => 'WHOLESALE']);
    }
}

