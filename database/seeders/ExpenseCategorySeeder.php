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
            ['name' => 'Rent & Property Expenses', 'code' => 'EXP-RENT', 'description' => 'Store & warehouse monthly rent'],
            ['name' => 'Electricity & Utilities', 'code' => 'EXP-UTIL', 'description' => 'Electricity, water, and internet bills'],
            ['name' => 'Staff Salaries & Allowances', 'code' => 'EXP-SALARY', 'description' => 'Monthly staff payroll'],
            ['name' => 'Maintenance & Repairs', 'code' => 'EXP-MAINT', 'description' => 'Equipment & facility repairs'],
            ['name' => 'Office Supplies & Stationery', 'code' => 'EXP-SUPPLY', 'description' => 'Paper, toner, and office consumables'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
