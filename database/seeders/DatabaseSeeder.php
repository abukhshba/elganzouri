<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CompanySettingsSeeder::class,
            DocumentNumberSequenceSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            WarehouseSeeder::class,
            PaymentTermSeeder::class,
            TaxSeeder::class,
            PriceListSeeder::class,
            ItemSeeder::class,
            ItemInventorySeeder::class,
            SupplierSeeder::class,
            CashboxSeeder::class,
            ExpenseCategorySeeder::class,
            CustomerSeeder::class,
        ]);
    }
}
