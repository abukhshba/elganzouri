<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PaymentTerm;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone2Test extends TestCase
{
    use RefreshDatabase;

    public function test_category_parent_child_hierarchy(): void
    {
        $parent = Category::create(['name' => 'Cookware', 'slug' => 'cookware']);
        $child = Category::create(['name' => 'Pots & Pans', 'slug' => 'pots-pans', 'parent_id' => $parent->id]);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertCount(1, $parent->children);
        $this->assertEquals('Pots & Pans', $parent->children->first()->name);
    }

    public function test_units_and_brands_master_creation(): void
    {
        $unit = Unit::create(['name' => 'Carton', 'short_name' => 'ctn']);
        $brand = Brand::create(['name' => 'Pyrex', 'slug' => 'pyrex']);

        $this->assertDatabaseHas('units', ['short_name' => 'ctn']);
        $this->assertDatabaseHas('brands', ['slug' => 'pyrex']);
    }

    public function test_warehouse_active_scope(): void
    {
        Warehouse::create(['name' => 'Active Central WH', 'code' => 'WH-A', 'is_active' => true]);
        Warehouse::create(['name' => 'Inactive Old WH', 'code' => 'WH-I', 'is_active' => false]);

        $activeWarehouses = Warehouse::active()->get();

        $this->assertCount(1, $activeWarehouses);
        $this->assertEquals('WH-A', $activeWarehouses->first()->code);
    }

    public function test_payment_term_and_tax_decimal_casting(): void
    {
        $term = PaymentTerm::create(['name' => 'Net 30 Days', 'days_due' => 30]);
        $tax = Tax::create(['name' => 'Standard VAT', 'code' => 'VAT14', 'rate_percentage' => 14.0000]);

        $this->assertEquals(30, $term->days_due);
        $this->assertEquals(14.0, $tax->rate_percentage);
    }
}
