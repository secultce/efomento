<?php

namespace Tests\Feature\Notice;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_budget_allocation_nup_must_be_unique(): void
    {
        $existing = Notice::factory()->create([
            'budget_allocation_nup' => '12345.678901/2024-01',
        ]);

        $notice = Notice::factory()->create([
            'budget_allocation_nup' => '99999.000001/2024-99',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('notices.update', $notice), [
                'budget_allocation_nup' => $existing->budget_allocation_nup,
            ]);

        $response->assertSessionHasErrors('budget_allocation_nup');
    }

    public function test_budget_allocation_nup_can_be_updated_to_same_value(): void
    {
        $notice = Notice::factory()->create([
            'budget_allocation_nup' => '12345.678901/2024-01',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('notices.update', $notice), [
                'budget_allocation_nup' => $notice->budget_allocation_nup,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_nup_must_be_unique(): void
    {
        $existing = Notice::factory()->create([
            'nup' => '12345.678901/2024-01',
        ]);

        $notice = Notice::factory()->create([
            'nup' => '99999.000001/2024-99',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('notices.update', $notice), [
                'nup' => $existing->nup,
            ]);

        $response->assertSessionHasErrors('nup');
    }
}
