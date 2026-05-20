<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_example(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/editais');

        $response->assertStatus(200);
    }
}
