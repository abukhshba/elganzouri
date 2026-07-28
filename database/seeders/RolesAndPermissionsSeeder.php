<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define system permissions
        $permissions = [
            // User & Security Management
            'manage_users',
            'manage_roles',
            'manage_settings',

            // Master Data
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
            'units.view', 'units.create', 'units.edit', 'units.delete',
            'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
            'payment_terms.view', 'payment_terms.create', 'payment_terms.edit', 'payment_terms.delete',
            'taxes.view', 'taxes.create', 'taxes.edit', 'taxes.delete',

            // Item Catalog
            'items.view', 'items.create', 'items.edit', 'items.delete',
            'price_lists.view', 'price_lists.create', 'price_lists.edit',

            // Inventory Engine & Operations
            'inventory.view', 'inventory.audit',
            'transfers.view', 'transfers.create', 'transfers.dispatch', 'transfers.receive',
            'adjustments.view', 'adjustments.create', 'adjustments.approve',

            // Purchasing Sub-system
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'purchases.view', 'purchases.create', 'purchases.confirm', 'purchases.cancel',
            'purchase_returns.view', 'purchase_returns.create', 'purchase_returns.confirm',

            // Sales Sub-system
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'sales.view', 'sales.create', 'sales.confirm', 'sales.cancel', 'sales.print',
            'sales_returns.view', 'sales_returns.create', 'sales_returns.confirm',

            // Treasury & Expenses
            'cashboxes.view', 'cashboxes.manage', 'cashboxes.open_close',
            'expenses.view', 'expenses.create', 'expenses.delete',

            // Reports
            'reports.view', 'reports.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Super Admin Role & Assign All Permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create Store Manager Role
        $managerRole = Role::firstOrCreate(['name' => 'Store Manager']);
        $managerRole->givePermissionTo([
            'categories.view', 'brands.view', 'units.view', 'warehouses.view',
            'items.view', 'items.create', 'items.edit',
            'inventory.view', 'transfers.view', 'transfers.create', 'transfers.dispatch', 'transfers.receive',
            'purchases.view', 'purchases.create', 'purchases.confirm',
            'sales.view', 'sales.create', 'sales.confirm', 'sales.print',
            'cashboxes.view', 'cashboxes.open_close', 'expenses.view', 'expenses.create',
            'reports.view',
        ]);

        // Create Cashier Role
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);
        $cashierRole->givePermissionTo([
            'items.view', 'inventory.view',
            'customers.view', 'customers.create',
            'sales.view', 'sales.create', 'sales.confirm', 'sales.print',
            'cashboxes.view', 'cashboxes.open_close',
        ]);

        // Create Default Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'name' => 'ERP Super Admin',
                'password' => bcrypt('password'),
            ]
        );

        $adminUser->assignRole($adminRole);
    }
}
