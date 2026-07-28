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
            [
                'name' => ['ar' => 'نقداً فورياً', 'en' => 'Immediate Cash'],
                'days_due' => 0,
                'description' => ['ar' => 'الدفع الفوري عند الاستلام', 'en' => 'Payment immediately upon receipt'],
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'آجل 15 يوماً', 'en' => 'Net 15 Days'],
                'days_due' => 15,
                'description' => ['ar' => 'السداد خلال 15 يوماً من تاريخ الفاتورة', 'en' => 'Payment due within 15 days'],
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'آجل 30 يوماً', 'en' => 'Net 30 Days'],
                'days_due' => 30,
                'description' => ['ar' => 'السداد خلال 30 يوماً من تاريخ الفاتورة', 'en' => 'Payment due within 30 days'],
                'is_active' => true,
            ],
        ];

        foreach ($terms as $term) {
            PaymentTerm::firstOrCreate(['days_due' => $term['days_due']], $term);
        }
    }
}
