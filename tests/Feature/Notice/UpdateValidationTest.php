<?php

namespace Tests\Feature\Notice;

use App\Enums\InstrumentType;
use App\Enums\MonitoringReportRequestDeadline;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $userWithRole;

    private User $userWithoutRole;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'fomentation', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'coord_fomentation', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->userWithRole = User::factory()->create();
        $this->userWithRole->assignRole('fomentation');

        $this->userWithoutRole = User::factory()->create();
    }

    // ─── Autorização ────────────────────────────────────────────────────────────

    public function test_user_without_role_cannot_update_notice(): void
    {
        $notice = Notice::factory()->create();

        $this->actingAs($this->userWithoutRole)
            ->patch(route('notices.update', $notice), ['nup' => '12345.678901/2024-01'])
            ->assertForbidden();
    }

    public function test_fomentation_role_can_update_notice(): void
    {
        $notice = Notice::factory()->create([
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($this->userWithRole)
            ->patch(route('notices.update', $notice), [
                'nup' => $notice->nup,
                'instrument_type' => $notice->instrument_type,
            ])
            ->assertRedirect();
    }

    public function test_super_admin_role_can_update_notice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $notice = Notice::factory()->create([
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($admin)
            ->patch(route('notices.update', $notice), [
                'nup' => $notice->nup,
                'instrument_type' => $notice->instrument_type,
            ])
            ->assertRedirect();
    }

    // ─── Unicidade ──────────────────────────────────────────────────────────────

    public function test_budget_allocation_nup_must_be_unique(): void
    {
        $existing = Notice::factory()->create([
            'budget_allocation_nup' => '12345.678901/2024-01',
        ]);

        $notice = Notice::factory()->create([
            'budget_allocation_nup' => '99999.000001/2024-99',
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($this->userWithRole)
            ->patch(route('notices.update', $notice), [
                'budget_allocation_nup' => $existing->budget_allocation_nup,
                'instrument_type' => $notice->instrument_type,
            ])
            ->assertSessionHasErrors('budget_allocation_nup');
    }

    public function test_budget_allocation_nup_can_be_updated_to_same_value(): void
    {
        $notice = Notice::factory()->create([
            'budget_allocation_nup' => '12345.678901/2024-01',
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($this->userWithRole)
            ->patch(route('notices.update', $notice), [
                'budget_allocation_nup' => $notice->budget_allocation_nup,
                'instrument_type' => $notice->instrument_type,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_nup_must_be_unique(): void
    {
        $existing = Notice::factory()->create([
            'nup' => '12345.678901/2024-01',
        ]);

        $notice = Notice::factory()->create([
            'nup' => '99999.000001/2024-99',
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($this->userWithRole)
            ->patch(route('notices.update', $notice), [
                'nup' => $existing->nup,
                'instrument_type' => $notice->instrument_type,
            ])
            ->assertSessionHasErrors('nup');
    }

    public function test_monitoring_report_request_deadline_can_be_updated(): void
    {
        $notice = Notice::factory()->create([
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($this->userWithRole)
            ->patch(route('notices.update', $notice), [
                'monitoring_report_request_deadline' => MonitoringReportRequestDeadline::MECENAS->value,
                'instrument_type' => $notice->instrument_type,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $notice->refresh();

        $this->assertSame(MonitoringReportRequestDeadline::MECENAS, $notice->monitoring_report_request_deadline);
        $this->assertSame(240, $notice->monitoring_report_request_deadline_days);
    }

    public function test_monitoring_report_request_deadline_must_be_a_supported_value(): void
    {
        $notice = Notice::factory()->create([
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($this->userWithRole)
            ->patch(route('notices.update', $notice), [
                'monitoring_report_request_deadline' => 'INVALIDO',
                'instrument_type' => $notice->instrument_type,
            ])
            ->assertSessionHasErrors('monitoring_report_request_deadline');
    }

    // ─── Instrument Type ────────────────────────────────────────────────────────

    public function test_instrument_type_is_required_on_update(): void
    {
        $notice = Notice::factory()->create([
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($this->userWithRole)
            ->patch(route('notices.update', $notice), [
                'nup' => $notice->nup,
                // instrument_type omitido intencionalmente
            ])
            ->assertSessionHasErrors('instrument_type');
    }

    public function test_instrument_type_cannot_be_null_on_update(): void
    {
        $notice = Notice::factory()->create([
            'instrument_type' => InstrumentType::EXECUCAO_CULTURAL->value,
        ]);

        $this->actingAs($this->userWithRole)
            ->patch(route('notices.update', $notice), [
                'instrument_type' => null,
            ])
            ->assertSessionHasErrors('instrument_type');
    }
}
