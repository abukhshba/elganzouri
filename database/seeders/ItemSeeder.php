<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\ItemUnit;
use App\Models\PriceList;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $piece = Unit::where('short_name', 'pc')->first();
        $pack = Unit::where('short_name', 'pk')->first();
        $carton = Unit::where('short_name', 'ctn')->first();

        $cookwareCat = Category::where('slug', 'cookware-kitchenware')->first();
        $glasswareCat = Category::where('slug', 'glassware-thermal')->first();

        $pyrexBrand = Brand::where('slug', 'pyrex')->first();
        $tefalBrand = Brand::where('slug', 'tefal')->first();

        $retailList = PriceList::where('code', 'RETAIL-EGP')->first();

        $items = [
            [
                'sku' => 'PYREX-BOWL-3L',
                'barcode' => '629100000001',
                'name' => ['ar' => 'وعاء زجاجي فرنسي حراري بايركس 3 لتر', 'en' => 'Pyrex French Thermal Glass Bowl 3L'],
                'description' => ['ar' => 'وعاء بيضاوي مقاوم للحرارة سعة 3 لتر مصنوع في فرنسا', 'en' => 'Heat resistant oval bowl 3 liters capacity made in France'],
                'category_id' => $glasswareCat?->id,
                'brand_id' => $pyrexBrand?->id,
                'base_unit_id' => $piece?->id,
                'min_stock_alert' => 10.0000,
                'is_active' => true,
                'units' => [
                    ['unit_id' => $piece?->id, 'conversion_factor' => 1.0, 'sale_price' => 250.0],
                    ['unit_id' => $carton?->id, 'conversion_factor' => 12.0, 'sale_price' => 2800.0],
                ],
            ],
            [
                'sku' => 'TEFAL-FRYPAN-28',
                'barcode' => '629100000002',
                'name' => ['ar' => 'مقلاة تيفال سينسـيشن مقاس 28 سم غير لاصقة', 'en' => 'Tefal Sensation Non-Stick Frying Pan 28cm'],
                'description' => ['ar' => 'مقلاة مصنوعة من الألمنيوم عالي الجودة مع طبقة تيتانيوم غير لاصقة', 'en' => 'High quality aluminum pan with titanium non-stick layer'],
                'category_id' => $cookwareCat?->id,
                'brand_id' => $tefalBrand?->id,
                'base_unit_id' => $piece?->id,
                'min_stock_alert' => 5.0000,
                'is_active' => true,
                'units' => [
                    ['unit_id' => $piece?->id, 'conversion_factor' => 1.0, 'sale_price' => 850.0],
                    ['unit_id' => $pack?->id, 'conversion_factor' => 6.0, 'sale_price' => 4900.0],
                ],
            ],
        ];

        foreach ($items as $itemData) {
            $units = $itemData['units'];
            unset($itemData['units']);

            $item = Item::firstOrCreate(['sku' => $itemData['sku']], $itemData);

            foreach ($units as $u) {
                $itemUnit = ItemUnit::firstOrCreate([
                    'item_id' => $item->id,
                    'unit_id' => $u['unit_id'],
                ], [
                    'conversion_factor' => $u['conversion_factor'],
                    'sale_price' => $u['sale_price'],
                ]);

                if ($retailList) {
                    ItemPrice::firstOrCreate([
                        'price_list_id' => $retailList->id,
                        'item_id' => $item->id,
                        'item_unit_id' => $itemUnit->id,
                    ], [
                        'price' => $u['sale_price'],
                    ]);
                }
            }
        }
    }
}
