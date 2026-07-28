<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\DocumentNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Milestone1Test extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_assigned_role_and_permissions(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Store Manager');

        $this->assertTrue($user->hasRole('Store Manager'));
        $this->assertTrue($user->hasPermissionTo('items.view'));
        $this->assertFalse($user->hasPermissionTo('manage_settings'));
    }

    public function test_settings_get_and_set_helper_functions(): void
    {
        Setting::set('test_company_name', 'Household Store Test', 'company', 'string');
        Setting::set('test_tax_rate', '14.5000', 'tax', 'decimal');
        Setting::set('test_negative_stock', 'false', 'system', 'boolean');

        $this->assertEquals('Household Store Test', Setting::get('test_company_name'));
        $this->assertEquals(14.5, Setting::get('test_tax_rate'));
        $this->assertFalse(Setting::get('test_negative_stock'));
    }

    public function test_document_numbering_service_generates_sequential_codes(): void
    {
        $this->seed(\Database\Seeders\DocumentNumberSequenceSeeder::class);

        $service = app(DocumentNumberingService::class);

        $code1 = $service->generateNextCode('PURCHASE');
        $code2 = $service->generateNextCode('PURCHASE');
        $code3 = $service->generateNextCode('SALE');

        $this->assertEquals('PO-00001', $code1);
        $this->assertEquals('PO-00002', $code2);
        $this->assertEquals('INV-000001', $code3);
    }
}
