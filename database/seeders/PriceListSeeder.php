<?php

namespace Database\Seeders;

use App\Models\PriceList;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lists = [
            ['name' => 'Standard Retail Price', 'code' => 'RETAIL', 'currency' => 'EGP', 'is_active' => true, 'is_default' => true],
            ['name' => 'Wholesale Price List', 'code' => 'WHOLESALE', 'currency' => 'EGP', 'is_active' => true, 'is_default' => false],
            ['name' => 'VIP Customer Price', 'code' => 'VIP', 'currency' => 'EGP', 'is_active' => true, 'is_default' => false],
        ];

        foreach ($lists as $list) {
            PriceList::firstOrCreate(['code' => $list['code']], $list);
        }
    }
}
