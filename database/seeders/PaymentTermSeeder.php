<?php

namespace Database\Seeders;

use App\Models\PaymentTerm;
use Illuminate\Database\Seeder;

class PaymentTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = [
            ['name' => 'Immediate Cash', 'days_due' => 0, 'is_active' => true, 'is_default' => true],
            ['name' => 'Net 7 Days', 'days_due' => 7, 'is_active' => true, 'is_default' => false],
            ['name' => 'Net 15 Days', 'days_due' => 15, 'is_active' => true, 'is_default' => false],
            ['name' => 'Net 30 Days', 'days_due' => 30, 'is_active' => true, 'is_default' => false],
            ['name' => 'Net 60 Days', 'days_due' => 60, 'is_active' => true, 'is_default' => false],
        ];

        foreach ($terms as $term) {
            PaymentTerm::firstOrCreate(['name' => $term['name']], $term);
        }
    }
}
