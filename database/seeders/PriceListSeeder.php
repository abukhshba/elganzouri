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
        $priceLists = [
            [
                'name' => ['ar' => 'قائمة أسعار التجزئة', 'en' => 'Standard Retail Price List'],
                'code' => 'RETAIL-EGP',
                'currency' => 'EGP',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => ['ar' => 'قائمة أسعار الجملة', 'en' => 'Wholesale Price List'],
                'code' => 'WHOLESALE-EGP',
                'currency' => 'EGP',
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($priceLists as $list) {
            PriceList::firstOrCreate(['code' => $list['code']], $list);
        }
    }
}
