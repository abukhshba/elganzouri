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
                'name' => 'Main Treasury Register',
                'warehouse_id' => $mainWh?->id,
                'current_balance' => 10000.0000,
                'is_active' => true,
            ],
            [
                'name' => 'POS Register #1 - Store Branch',
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
