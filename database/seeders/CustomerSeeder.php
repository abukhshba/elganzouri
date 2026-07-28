<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\PaymentTerm;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cashTerm = PaymentTerm::where('name', 'Immediate Cash')->first();
        $net15Term = PaymentTerm::where('name', 'Net 15 Days')->first();

        $customers = [
            [
                'name' => 'Walk-in Customer',
                'email' => 'walkin@store.com',
                'phone' => '+20 100 000 0001',
                'payment_term_id' => $cashTerm?->id,
                'credit_limit' => 0.0000,
                'balance' => 0.0000,
                'is_active' => true,
            ],
            [
                'name' => 'Al-Ahram Commercial Trading Co.',
                'email' => 'orders@alahram-trading.com',
                'phone' => '+20 2 2444 5555',
                'address' => 'Nasr City, Building 18, Cairo',
                'tax_number' => 'TAX-CUST-9988',
                'payment_term_id' => $net15Term?->id,
                'credit_limit' => 50000.0000,
                'balance' => 0.0000,
                'is_active' => true,
            ],
        ];

        foreach ($customers as $cust) {
            Customer::firstOrCreate(['name' => $cust['name']], $cust);
        }
    }
}
