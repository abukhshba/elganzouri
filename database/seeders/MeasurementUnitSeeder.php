<?php

namespace Database\Seeders;

use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class MeasurementUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Weight Group
        $weight = MeasurementUnit::create([
            'name' => ['en' => 'Weight', 'ar' => 'الوزن'],
            'slug' => 'weight',
        ]);

        $weight->details()->create([
            'name' => ['en' => 'Gram', 'ar' => 'جرام'],
            'unit_type' => 'weight',
            'is_default' => true,
            'value' => 1,
        ]);

        $weight->details()->create([
            'name' => ['en' => 'Kilogram', 'ar' => 'كيلوجرام'],
            'unit_type' => 'weight',
            'is_default' => false,
            'value' => 1000,
        ]);

        $weight->details()->create([
            'name' => ['en' => 'Ton', 'ar' => 'طن'],
            'unit_type' => 'weight',
            'is_default' => false,
            'value' => 1000000,
        ]);

        // 2. Length Group
        $length = MeasurementUnit::create([
            'name' => ['en' => 'Length', 'ar' => 'الطول'],
            'slug' => 'length',
        ]);

        $length->details()->create([
            'name' => ['en' => 'Centimeter', 'ar' => 'سنتيمتر'],
            'unit_type' => 'length',
            'is_default' => true,
            'value' => 1,
        ]);

        $length->details()->create([
            'name' => ['en' => 'Meter', 'ar' => 'متر'],
            'unit_type' => 'length',
            'is_default' => false,
            'value' => 100,
        ]);

        // 3. Packaging/Units Group
        $packaging = MeasurementUnit::create([
            'name' => ['en' => 'Packaging', 'ar' => 'تعبئة وتغليف'],
            'slug' => 'packaging',
        ]);

        $packaging->details()->create([
            'name' => ['en' => 'Piece', 'ar' => 'قطعة'],
            'unit_type' => 'numbers',
            'is_default' => true,
            'value' => 1,
        ]);

        $packaging->details()->create([
            'name' => ['en' => 'Carton - 24 pcs', 'ar' => 'كرتونة - 24 قطعة'],
            'unit_type' => 'numbers',
            'is_default' => false,
            'value' => 24,
        ]);

        $packaging->details()->create([
            'name' => ['en' => 'Carton - 72 pcs', 'ar' => 'كرتونة - 72 قطعة'],
            'unit_type' => 'numbers',
            'is_default' => false,
            'value' => 72,
        ]);
    }
}
