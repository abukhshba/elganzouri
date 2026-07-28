<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => ['ar' => 'أدوات المطبخ والطهي', 'en' => 'Cookware & Kitchenware'],
                'slug' => 'cookware-kitchenware',
            ],
            [
                'name' => ['ar' => 'الأدوات الزجاجية والحرارية', 'en' => 'Glassware & Thermal Items'],
                'slug' => 'glassware-thermal',
            ],
            [
                'name' => ['ar' => 'الأجهزة المنزلية الصغيرة', 'en' => 'Small Home Appliances'],
                'slug' => 'small-home-appliances',
            ],
            [
                'name' => ['ar' => 'أدوات التنظيف والتنظيم', 'en' => 'Cleaning & Storage Supplies'],
                'slug' => 'cleaning-storage',
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
