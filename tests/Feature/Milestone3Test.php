<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\PriceList;
use App\Models\Unit;
use App\Services\UomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone3Test extends TestCase
{
    use RefreshDatabase;

    public function test_item_creation_with_base_unit_and_multi_uom_matrix(): void
    {
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $carton = Unit::create(['name' => 'Carton', 'short_name' => 'ctn']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-CUP-101',
            'name' => 'Luminarc Glass Cup',
        ]);

        $itemUnit = ItemUnit::create([
            'item_id' => $item->id,
            'unit_id' => $carton->id,
            'conversion_factor' => 72.0000,
            'purchase_price' => 720.0000,
        ]);

        $this->assertEquals($piece->id, $item->baseUnit->id);
        $this->assertCount(1, $item->itemUnits);
        $this->assertEquals(72.0, $itemUnit->conversion_factor);
    }

    public function test_uom_service_quantity_and_price_conversions(): void
    {
        $piece = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $pack = Unit::create(['name' => 'Pack', 'short_name' => 'pk']);
        $category = Category::create(['name' => 'Glassware', 'slug' => 'glassware']);

        $item = Item::create([
            'category_id' => $category->id,
            'base_unit_id' => $piece->id,
            'sku' => 'ITEM-CUP-102',
            'name' => 'Test Cup',
        ]);

        ItemUnit::create([
            'item_id' => $item->id,
            'unit_id' => $pack->id,
            'conversion_factor' => 6.0000,
        ]);

        $service = app(UomService::class);

        // 10 Packs * 6 = 60 Pieces
        $baseQty = $service->convertQuantityToBaseUnit($item->id, $pack->id, 10.0);
        $this->assertEquals(60.0, $baseQty);

        // Price $90 per Pack / 6 = $15 per Piece
        $basePrice = $service->convertPriceToBaseUnit($item->id, $pack->id, 90.0);
        $this->assertEquals(15.0, $basePrice);

        // Base unit factor is strictly 1.0
        $baseQtySameUnit = $service->convertQuantityToBaseUnit($item->id, $piece->id, 5.0);
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
