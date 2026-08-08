<?php

namespace Database\Seeders;

use App\Models\UnitGroup;
use Illuminate\Database\Seeder;

class UnitGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => ['en' => 'Packaging & Quantity', 'ar' => 'التعبئة والتغليف والكمية']],
            ['name' => ['en' => 'Weight', 'ar' => 'الوزن']],
            ['name' => ['en' => 'Length', 'ar' => 'الطول']],
            ['name' => ['en' => 'Volume', 'ar' => 'الحجم']],
        ];

        foreach ($groups as $group) {
            UnitGroup::updateOrCreate(
                ['name->en' => $group['name']['en']],
                $group
            );
        }
    }
}
