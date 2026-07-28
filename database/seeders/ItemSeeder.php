<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
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

        $glassCategory = Category::where('slug', 'drinkware-cups')->first() ?? Category::first();
        $cookwareCategory = Category::where('slug', 'pots-pans')->first() ?? Category::first();

        $luminarc = Brand::where('slug', 'luminarc')->first();
        $tefal = Brand::where('slug', 'tefal')->first();

        // 1. Luminarc Glass Cup
        $cup = Item::firstOrCreate(
            ['sku' => 'ITEM-CUP-001'],
            [
                'name' => 'Luminarc Glass Cup 250ml',
                'barcode' => '629100100201',
                'category_id' => $glassCategory->id,
                'brand_id' => $luminarc?->id,
                'base_unit_id' => $piece->id,
                'description' => 'Premium French tempered glass cup 250ml.',
                'min_stock_alert' => 50.0000,
                'is_active' => true,
            ]
        );

        // Multi-UOM units for Glass Cup
        ItemUnit::firstOrCreate(
            ['item_id' => $cup->id, 'unit_id' => $pack->id],
            [
                'conversion_factor' => 6.0000, // 1 Pack = 6 Pieces
                'barcode' => '629100100206',
                'purchase_price' => 60.0000,
                'sale_price' => 90.0000,
                'is_default_sale' => true,
            ]
        );

        ItemUnit::firstOrCreate(
            ['item_id' => $cup->id, 'unit_id' => $carton->id],
            [
                'conversion_factor' => 72.0000, // 1 Carton = 72 Pieces
                'barcode' => '629100100272',
                'purchase_price' => 650.0000,
                'sale_price' => 950.0000,
                'is_default_purchase' => true,
            ]
        );

        // 2. Tefal Frying Pan
        $pan = Item::firstOrCreate(
            ['sku' => 'ITEM-PAN-028'],
            [
                'name' => 'Tefal Non-Stick Frying Pan 28cm',
                'barcode' => '316843028001',
                'category_id' => $cookwareCategory->id,
                'brand_id' => $tefal?->id,
                'base_unit_id' => $piece->id,
                'description' => 'Tefal Thermo-Signal 28cm non-stick frying pan.',
                'min_stock_alert' => 10.0000,
                'is_active' => true,
            ]
        );

        ItemUnit::firstOrCreate(
            ['item_id' => $pan->id, 'unit_id' => $carton->id],
            [
                'conversion_factor' => 10.0000, // 1 Carton = 10 Pans
                'barcode' => '316843028010',
                'purchase_price' => 3200.0000,
                'sale_price' => 4500.0000,
                'is_default_purchase' => true,
                'is_default_sale' => false,
            ]
        );
    }
}
