<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Cookware & Kitchenware' => ['Pots & Pans', 'Bakeware', 'Kitchen Utensils'],
            'Glassware & Tableware' => ['Drinkware Cups', 'Dinner Sets', 'Bowls'],
            'Cleaning & Storage' => ['Food Storage Containers', 'Cleaning Supplies'],
            'Home Appliances' => ['Blenders & Mixers', 'Coffee Makers'],
        ];

        foreach ($categories as $parentName => $subCategories) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($parentName)],
                ['name' => $parentName, 'parent_id' => null]
            );

            foreach ($subCategories as $subName) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($subName)],
                    ['name' => $subName, 'parent_id' => $parent->id]
                );
            }
        }
    }
}
