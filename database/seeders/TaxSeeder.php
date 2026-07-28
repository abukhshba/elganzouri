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
            [
                'name' => ['ar' => 'ضريبة القيمة المضافة 14%', 'en' => 'Value Added Tax (VAT) 14%'],
                'code' => 'VAT14',
                'rate_percentage' => 14.0000,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'معفى من الضريبة (0%)', 'en' => 'Zero Tax (0%)'],
                'code' => 'ZERO',
                'rate_percentage' => 0.0000,
                'is_active' => true,
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::firstOrCreate(['code' => $tax['code']], $tax);
        }
    }
}
