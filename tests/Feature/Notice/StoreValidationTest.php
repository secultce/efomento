<?php

namespace Tests\Feature\Notice;

use App\Enums\MonitoringReportRequestDeadline;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ─── Campos obrigatórios ────────────────────────────────────────────────────

    public function test_nup_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('notices.store'), ['name' => 'Edital Teste'])
            ->assertSessionHasErrors('nup');
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('notices.store'), ['nup' => '12345.678901/2024-01'])
            ->assertSessionHasErrors('name');
    }

    // ─── Unicidade ──────────────────────────────────────────────────────────────

    public function test_nup_must_be_unique_on_store(): void
    {
        Notice::factory()->create(['nup' => '12345.678901/2024-01']);

        $this->actingAs($this->user)
            ->post(route('notices.store'), [
                'nup' => '12345.678901/2024-01',
                'name' => 'Outro Edital',
            ])
            ->assertSessionHasErrors('nup');
    }

    public function test_different_nup_can_be_stored(): void
    {
        Notice::factory()->create(['nup' => '12345.678901/2024-01']);

        $this->actingAs($this->user)
            ->post(route('notices.store'), [
                'nup' => '99999.000001/2024-99',
                'name' => 'Outro Edital',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_monitoring_report_request_deadline_must_be_a_supported_value(): void
    {
        $this->actingAs($this->user)
            ->post(route('notices.store'), [
                'nup' => '99999.000001/2024-99',
                'name' => 'Edital Teste',
                'monitoring_report_request_deadline' => 'INVALIDO',
            ])
            ->assertSessionHasErrors('monitoring_report_request_deadline');
    }

    public function test_monitoring_report_request_deadline_can_be_stored(): void
    {
        $this->actingAs($this->user)
            ->post(route('notices.store'), [
                'nup' => '99999.000001/2024-99',
                'name' => 'Edital Teste',
                'monitoring_report_request_deadline' => MonitoringReportRequestDeadline::CICLOS_CALENDARIZADOS->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notices', [
            'nup' => '99999.000001/2024-99',
            'monitoring_report_request_deadline' => MonitoringReportRequestDeadline::CICLOS_CALENDARIZADOS->value,
        ]);
    }
}
