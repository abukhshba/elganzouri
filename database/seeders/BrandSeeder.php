<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => ['ar' => 'بايركس', 'en' => 'Pyrex'], 'slug' => 'pyrex'],
            ['name' => ['ar' => 'تيفال', 'en' => 'Tefal'], 'slug' => 'tefal'],
            ['name' => ['ar' => 'لومينارك', 'en' => 'Luminarc'], 'slug' => 'luminarc'],
            ['name' => ['ar' => 'براون', 'en' => 'Braun'], 'slug' => 'braun'],
            ['name' => ['ar' => 'زينوكس', 'en' => 'Zinox'], 'slug' => 'zinox'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['slug' => $brand['slug']], $brand);
        }
    }
}
