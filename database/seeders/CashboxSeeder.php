<?php

namespace Database\Seeders;

use App\Models\Cashbox;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class CashboxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainWh = Warehouse::where('code', 'WH-MAIN')->first();
        $storeWh = Warehouse::where('code', 'WH-STORE1')->first();

        $cashboxes = [
            [
                'name' => 'خزينة الخزينة الرئيسية',
                'warehouse_id' => $mainWh?->id,
                'current_balance' => 10000.0000,
                'is_active' => true,
            ],
            [
                'name' => 'خزينة نقطة البيع #1 - الفرع',
                'warehouse_id' => $storeWh?->id,
                'current_balance' => 2000.0000,
                'is_active' => true,
            ],
        ];

        foreach ($cashboxes as $cb) {
            Cashbox::firstOrCreate(['name' => $cb['name']], $cb);
        }
    }
}
