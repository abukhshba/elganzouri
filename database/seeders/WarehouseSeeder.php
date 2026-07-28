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
                'name' => 'Main Central Warehouse',
                'code' => 'WH-MAIN',
                'phone' => '+20 100 111 2222',
                'address' => 'Industrial Zone, Building 45, Cairo',
                'is_active' => true,
            ],
            [
                'name' => 'Retail Store Branch #1',
                'code' => 'WH-STORE1',
                'phone' => '+20 100 333 4444',
                'address' => 'Downtown Commercial Mall, Shop 12',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::firstOrCreate(['code' => $wh['code']], $wh);
        }
    }
}
