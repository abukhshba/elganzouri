<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Company Information
            ['key' => 'company_name', 'value' => 'Elganzouri Household Store', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_address', 'value' => '123 Enterprise Avenue, Commercial District', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_phone', 'value' => '+20 100 000 0000', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_email', 'value' => 'info@elganzouri.com', 'group' => 'company', 'type' => 'string'],
            ['key' => 'company_tax_number', 'value' => 'TAX-9988776655', 'group' => 'company', 'type' => 'string'],

            // Currency Settings
            ['key' => 'currency_code', 'value' => 'EGP', 'group' => 'currency', 'type' => 'string'],
            ['key' => 'currency_symbol', 'value' => 'EGP', 'group' => 'currency', 'type' => 'string'],
            ['key' => 'currency_decimal_places', 'value' => '4', 'group' => 'currency', 'type' => 'integer'],

            // Tax Settings
            ['key' => 'tax_mode', 'value' => 'TAX_EXCLUSIVE', 'group' => 'tax', 'type' => 'string'],
            ['key' => 'default_tax_rate', 'value' => '14.0000', 'group' => 'tax', 'type' => 'decimal'],

            // System Preferences
            ['key' => 'allow_negative_stock', 'value' => 'false', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'thermal_receipt_header', 'value' => 'Welcome to Elganzouri Household Store!', 'group' => 'print', 'type' => 'string'],
            ['key' => 'thermal_receipt_footer', 'value' => 'Thank you for shopping with us! Returns accepted within 14 days.', 'group' => 'print', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
