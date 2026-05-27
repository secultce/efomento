<?php

namespace Tests\Feature\Notice;

use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAuditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_registers_old_and_new_values(): void
    {
        $notice = Notice::factory()->create([
            'name' => 'Nome Antigo',
        ]);

        $notice->update([
            'name' => 'Nome Novo',
        ]);

        $audit = $notice->audits()->where('event', 'updated')->latest()->first();

        $this->assertNotNull($audit);
        $this->assertEquals('Nome Antigo', $audit->old_values['name']);
        $this->assertEquals('Nome Novo', $audit->new_values['name']);
    }

    public function test_creating_notice_registers_audit(): void
    {
        $notice = Notice::factory()->create();

        $this->assertDatabaseHas('audits', [
            'auditable_type' => 'notice',
            'auditable_id' => $notice->id,
            'event' => 'created',
        ]);
    }

    public function test_updating_amount_and_date_registers_old_and_new_values(): void
    {
        $oldAmount = '10000.00';
        $newAmount = '25000.00';
        $oldDate = '2024-01-15';
        $newDate = '2024-06-30';

        $notice = Notice::factory()->create([
            'budget_allocation_nup' => 'BA-99999',
            'total_notice_amount' => $oldAmount,
            'creditor_registration_request_date' => $oldDate,
        ]);

        $notice->update([
            'total_notice_amount' => $newAmount,
            'creditor_registration_request_date' => $newDate,
        ]);

        $audit = $notice->audits()->where('event', 'updated')->latest()->first();

        $this->assertNotNull($audit);

        $this->assertEquals($oldAmount, $audit->old_values['total_notice_amount']);
        $this->assertEquals($newAmount, $audit->new_values['total_notice_amount']);

        $this->assertEquals("$oldDate 00:00:00", $audit->old_values['creditor_registration_request_date']);
        $this->assertEquals("$newDate 00:00:00", $audit->new_values['creditor_registration_request_date']);

        // Campos não alterados não devem aparecer no audit
        $this->assertArrayNotHasKey('budget_allocation_nup', $audit->old_values);
        $this->assertArrayNotHasKey('budget_allocation_nup', $audit->new_values);
    }
}
