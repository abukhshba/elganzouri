<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => ['ar' => 'المخزن الرئيسي - القاهرة', 'en' => 'Main Warehouse - Cairo'],
                'code' => 'WH-MAIN',
                'address' => 'المنطقة الصناعية - العبور',
                'phone' => '+20 2 4444 1111',
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'مخزن صالة العرض #1', 'en' => 'Store Showroom Warehouse #1'],
                'code' => 'WH-STORE1',
                'address' => 'شارع 90 - التجمع الخامس',
                'phone' => '+20 2 4444 2222',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::firstOrCreate(['code' => $wh['code']], $wh);
        }
    }
}
