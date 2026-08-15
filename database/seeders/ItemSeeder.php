<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;
use App\Models\PriceList;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packagingGroup = MeasurementUnit::where('slug', 'packaging')->first();
        
        $piece = MeasurementUnitDetail::where('name->en', 'Piece')->first();
        $carton24 = MeasurementUnitDetail::where('name->en', 'Carton - 24 pcs')->first();
        $carton72 = MeasurementUnitDetail::where('name->en', 'Carton - 72 pcs')->first();

        $cookwareCat = Category::where('slug', 'cookware-kitchenware')->first();
        $glasswareCat = Category::where('slug', 'glassware-thermal')->first();

        $pyrexBrand = Brand::where('slug', 'pyrex')->first();
        $tefalBrand = Brand::where('slug', 'tefal')->first();

        $retailList = PriceList::where('code', 'RETAIL-EGP')->first();

        $items = [
            [
                'sku' => 'CUP-CERAMIC-01',
                'barcode' => '629100000003',
                'name' => ['ar' => 'كوب سيراميك فاخر', 'en' => 'Ceramic Cup'],
                'description' => ['ar' => 'كوب سيراميك حراري ملون 350 مل', 'en' => 'Heat resistant colored ceramic cup 350ml'],
                'category_id' => $glasswareCat?->id,
                'brand_id' => $pyrexBrand?->id,
                'measurement_unit_id' => $packagingGroup?->id,
                'min_stock_alert' => 20.0000,
                'is_active' => true,
                'prices' => [
                    ['unit_id' => $piece?->id, 'sale_price' => 45.0],
                    ['unit_id' => $carton72?->id, 'sale_price' => 3100.0],
                ],
            ],
            [
                'sku' => 'PLATE-DINNER-01',
                'barcode' => '629100000004',
                'name' => ['ar' => 'طبق عشاء سيراميك', 'en' => 'Dinner Plate'],
                'description' => ['ar' => 'طبق عشاء كبير مقاس 26 سم', 'en' => 'Large dinner plate 26cm'],
                'category_id' => $glasswareCat?->id,
                'brand_id' => $pyrexBrand?->id,
                'measurement_unit_id' => $packagingGroup?->id,
                'min_stock_alert' => 15.0000,
                'is_active' => true,
                'prices' => [
                    ['unit_id' => $piece?->id, 'sale_price' => 85.0],
                    ['unit_id' => $carton24?->id, 'sale_price' => 1950.0],
                ],
            ],
        ];

        foreach ($items as $itemData) {
            $prices = $itemData['prices'];
            unset($itemData['prices']);

            $item = Item::firstOrCreate(['sku' => $itemData['sku']], $itemData);

            if ($retailList) {
                foreach ($prices as $p) {
                    ItemPrice::firstOrCreate([
                        'price_list_id' => $retailList->id,
                        'item_id' => $item->id,
                        'measurement_unit_detail_id' => $p['unit_id'],
                    ], [
                        'price' => $p['sale_price'],
                    ]);
                }
            }
        }
    }
}
