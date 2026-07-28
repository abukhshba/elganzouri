<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => ['ar' => 'قطعة', 'en' => 'Piece'], 'short_name' => 'pc'],
            ['name' => ['ar' => 'علبة', 'en' => 'Pack'], 'short_name' => 'pk'],
            ['name' => ['ar' => 'كرتونة', 'en' => 'Carton'], 'short_name' => 'ctn'],
            ['name' => ['ar' => 'دستة', 'en' => 'Dozen'], 'short_name' => 'doz'],
            ['name' => ['ar' => 'طقم', 'en' => 'Set'], 'short_name' => 'set'],
            ['name' => ['ar' => 'كيلوجرام', 'en' => 'Kilogram'], 'short_name' => 'kg'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['short_name' => $unit['short_name']], $unit);
        }
    }
}
