<?php

namespace Tests\Feature;

use App\Models\MeasurementUnit;
use App\Models\MeasurementUnitDetail;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }
}






