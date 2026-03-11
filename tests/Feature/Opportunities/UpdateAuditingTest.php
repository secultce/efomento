<?php

namespace Tests\Feature\Opportunities;

use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAuditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_opportunity_registers_audit(): void
    {
        $opportunity = Opportunity::factory()->create([
            'name' => 'Projeto Original',
        ]);

        $opportunity->update([
            'name' => 'Projeto Atualizado',
        ]);

        $this->assertDatabaseHas('audits', [
            'auditable_type' => Opportunity::class,
            'auditable_id'   => $opportunity->id,
            'event'          => 'updated',
        ]);
    }

    public function test_audit_registers_old_and_new_values(): void
    {
        $opportunity = Opportunity::factory()->create([
            'name' => 'Nome Antigo',
        ]);

        $opportunity->update([
            'name' => 'Nome Novo',
        ]);

        $audit = $opportunity->audits()->where('event', 'updated')->latest()->first();

        $this->assertNotNull($audit);
        $this->assertEquals('Nome Antigo', $audit->old_values['name']);
        $this->assertEquals('Nome Novo', $audit->new_values['name']);
    }

    public function test_creating_opportunity_registers_audit(): void
    {
        $opportunity = Opportunity::factory()->create();

        $this->assertDatabaseHas('audits', [
            'auditable_type' => Opportunity::class,
            'auditable_id'   => $opportunity->id,
            'event'          => 'created',
        ]);
    }

    public function test_updating_amount_and_date_registers_old_and_new_values(): void
    {
        $opportunity = Opportunity::factory()->create([
            'budget_allocation_nup'              => 'BA-99999',
            'total_opportunity_amount'           => '10000.00',
            'creditor_registration_request_date' => '2024-01-15',
        ]);

        $opportunity->update([
            'total_opportunity_amount'           => '25000.00',
            'creditor_registration_request_date' => '2024-06-30',
        ]);

        $this->assertDatabaseHas('audits', [
            'auditable_type' => Opportunity::class,
            'auditable_id'   => $opportunity->id,
            'event'          => 'updated',
        ]);

        $audit = $opportunity->audits()->where('event', 'updated')->latest()->first();

        $this->assertNotNull($audit);

        $this->assertEquals('10000.00', $audit->old_values['total_opportunity_amount']);
        $this->assertEquals('25000.00', $audit->new_values['total_opportunity_amount']);

        $this->assertEquals('2024-01-15 00:00:00', $audit->old_values['creditor_registration_request_date']);
        $this->assertEquals('2024-06-30 00:00:00', $audit->new_values['creditor_registration_request_date']);

        $this->assertArrayNotHasKey('budget_allocation_nup', $audit->old_values);
        $this->assertArrayNotHasKey('budget_allocation_nup', $audit->new_values);
    }
}
