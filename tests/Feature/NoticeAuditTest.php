<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_audits_for_a_notice()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $notice = Notice::factory()->create();

        $audit1 = $notice->audits()->create([
            'event' => 'created',
            'user_id' => $user->id,
            'old_values' => [],
            'new_values' => ['title' => 'Teste 1'],
            'created_at' => now()->subDay(),
        ]);

        $audit2 = $notice->audits()->create([
            'event' => 'updated',
            'user_id' => $user->id,
            'old_values' => ['title' => 'Teste 1'],
            'new_values' => ['title' => 'Teste 2'],
            'created_at' => now(),
        ]);

        $response = $this->getJson(route('notices.audits', $notice));

        $response->assertOk();

        $response->assertJsonCount(2, 'data');

        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'event',
                    'user',
                    'date',
                    'old_values',
                    'new_values',
                ],
            ],
        ]);

        // Verifica ordenação (latest primeiro)
        $this->assertEquals($audit2->id, $response->json('data.0.id'));
    }
}
