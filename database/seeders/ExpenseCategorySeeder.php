<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => ['ar' => 'مصاريف الإيجار والعقارات', 'en' => 'Rent & Property Expenses'],
                'code' => 'EXP-RENT',
                'description' => ['ar' => 'إيجار المتاجر والمخازن', 'en' => 'Store & warehouse monthly rent'],
            ],
            [
                'name' => ['ar' => 'الكهرباء والمرافق الخدمية', 'en' => 'Electricity & Utilities'],
                'code' => 'EXP-UTIL',
                'description' => ['ar' => 'فواتير الكهرباء والمياه والإنترنت', 'en' => 'Electricity, water, and internet bills'],
            ],
            [
                'name' => ['ar' => 'مرتبات وأجور العاملين', 'en' => 'Staff Salaries & Allowances'],
                'code' => 'EXP-SALARY',
                'description' => ['ar' => 'الرواتب الشهرية والبدلات', 'en' => 'Monthly staff payroll'],
            ],
            [
                'name' => ['ar' => 'الصيانة والإصلاحات', 'en' => 'Maintenance & Repairs'],
                'code' => 'EXP-MAINT', 'description' => ['ar' => 'صيانة المعدات والمرافق', 'en' => 'Equipment & facility repairs'],
            ],
            [
                'name' => ['ar' => 'المستلزمات المكتبية والورقيات', 'en' => 'Office Supplies & Stationery'],
                'code' => 'EXP-SUPPLY',
                'description' => ['ar' => 'أوراق وأحبار ومطبوعات', 'en' => 'Paper, toner, and office consumables'],
            ],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
