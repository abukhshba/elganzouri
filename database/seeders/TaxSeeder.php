<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            ['name' => 'Standard VAT 14%', 'code' => 'VAT14', 'rate_percentage' => 14.0000, 'is_active' => true],
            ['name' => 'Zero Tax 0%', 'code' => 'VAT0', 'rate_percentage' => 0.0000, 'is_active' => true],
            ['name' => 'Reduced Rate 5%', 'code' => 'VAT5', 'rate_percentage' => 5.0000, 'is_active' => true],
        ];

        foreach ($taxes as $tax) {
            Tax::firstOrCreate(['code' => $tax['code']], $tax);
        }
    }
}
