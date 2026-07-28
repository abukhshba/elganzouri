<?php

namespace Database\Seeders;

use App\Models\PaymentTerm;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $net30 = PaymentTerm::where('days_due', 30)->first();

        $suppliers = [
            [
                'name' => 'شركة بايركس الشرق الأوسط للتوزيع',
                'company_name' => 'Pyrex ME LLC',
                'email' => 'sales@pyrex-me.com',
                'phone' => '+20 2 2777 8888',
                'address' => 'القاهرة - برج المركز التجاري',
                'tax_number' => 'TAX-PYREX-001',
                'payment_term_id' => $net30?->id,
                'balance' => 0.0000,
                'is_active' => true,
            ],
            [
                'name' => 'الوكيل الرئيسي لتيفال مصر',
                'company_name' => 'SEB Groupe Egypt',
                'email' => 'orders@tefal-egypt.com',
                'phone' => '+20 2 2999 0000',
                'address' => 'القاهرة الجديدة - شارع 90',
                'tax_number' => 'TAX-TEFAL-002',
                'payment_term_id' => $net30?->id,
                'balance' => 0.0000,
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $sup) {
            Supplier::firstOrCreate(['name' => $sup['name']], $sup);
        }
    }
}
