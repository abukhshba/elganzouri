<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitGroup;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $pkgGroup = UnitGroup::where('name->en', 'Packaging & Quantity')->first();
        $weightGroup = UnitGroup::where('name->en', 'Weight')->first();
        $lengthGroup = UnitGroup::where('name->en', 'Length')->first();
        $volGroup = UnitGroup::where('name->en', 'Volume')->first();

        // 1. Packaging Group Units
        $pc = Unit::updateOrCreate(['short_name' => 'pc'], [
            'unit_group_id' => $pkgGroup?->id,
            'name' => ['ar' => 'قطعة', 'en' => 'Piece'],
            'is_base' => true,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 1.0000,
        ]);
        if ($pkgGroup) {
            $pkgGroup->update(['base_unit_id' => $pc->id]);
        }

        Unit::updateOrCreate(['short_name' => 'doz'], [
            'unit_group_id' => $pkgGroup?->id,
            'name' => ['ar' => 'دستة', 'en' => 'Dozen'],
            'is_base' => false,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 12.0000,
        ]);

        Unit::updateOrCreate(['short_name' => 'ctn'], [
            'unit_group_id' => $pkgGroup?->id,
            'name' => ['ar' => 'كرتونة', 'en' => 'Carton'],
            'is_base' => false,
            'is_custom_per_item' => true,
            'global_conversion_factor' => 1.0000,
        ]);

        Unit::updateOrCreate(['short_name' => 'pk'], [
            'unit_group_id' => $pkgGroup?->id,
            'name' => ['ar' => 'علبة', 'en' => 'Pack'],
            'is_base' => false,
            'is_custom_per_item' => true,
            'global_conversion_factor' => 1.0000,
        ]);

        Unit::updateOrCreate(['short_name' => 'set'], [
            'unit_group_id' => $pkgGroup?->id,
            'name' => ['ar' => 'طقم', 'en' => 'Set'],
            'is_base' => false,
            'is_custom_per_item' => true,
            'global_conversion_factor' => 1.0000,
        ]);

        // 2. Weight Group Units
        $gram = Unit::updateOrCreate(['short_name' => 'g'], [
            'unit_group_id' => $weightGroup?->id,
            'name' => ['ar' => 'جرام', 'en' => 'Gram'],
            'is_base' => true,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 1.0000,
        ]);
        if ($weightGroup) {
            $weightGroup->update(['base_unit_id' => $gram->id]);
        }

        Unit::updateOrCreate(['short_name' => 'kg'], [
            'unit_group_id' => $weightGroup?->id,
            'name' => ['ar' => 'كيلوجرام', 'en' => 'Kilogram'],
            'is_base' => false,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 1000.0000,
        ]);

        Unit::updateOrCreate(['short_name' => 't'], [
            'unit_group_id' => $weightGroup?->id,
            'name' => ['ar' => 'طن', 'en' => 'Ton'],
            'is_base' => false,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 1000000.0000,
        ]);

        // 3. Length Group Units
        $cm = Unit::updateOrCreate(['short_name' => 'cm'], [
            'unit_group_id' => $lengthGroup?->id,
            'name' => ['ar' => 'سنتيمتر', 'en' => 'Centimeter'],
            'is_base' => true,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 1.0000,
        ]);
        if ($lengthGroup) {
            $lengthGroup->update(['base_unit_id' => $cm->id]);
        }

        Unit::updateOrCreate(['short_name' => 'm'], [
            'unit_group_id' => $lengthGroup?->id,
            'name' => ['ar' => 'متر', 'en' => 'Meter'],
            'is_base' => false,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 100.0000,
        ]);

        // 4. Volume Group Units
        $ml = Unit::updateOrCreate(['short_name' => 'ml'], [
            'unit_group_id' => $volGroup?->id,
            'name' => ['ar' => 'مللي لتر', 'en' => 'Milliliter'],
            'is_base' => true,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 1.0000,
        ]);
        if ($volGroup) {
            $volGroup->update(['base_unit_id' => $ml->id]);
        }

        Unit::updateOrCreate(['short_name' => 'l'], [
            'unit_group_id' => $volGroup?->id,
            'name' => ['ar' => 'لتر', 'en' => 'Liter'],
            'is_base' => false,
            'is_custom_per_item' => false,
            'global_conversion_factor' => 1000.0000,
        ]);
    }
}
