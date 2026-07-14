<?php

namespace Tests\Feature;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Notice;
use App\Models\Project;
use App\Services\NoticeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeStatusTest extends TestCase
{
    use RefreshDatabase;

    private function statusFor(Notice $notice): string
    {
        return app(NoticeService::class)
            ->getNoticesForDashboard()
            ->firstWhere('id', $notice->id)['status'];
    }

    public function test_notice_without_nup_is_pendente_abertura(): void
    {
        $notice = Notice::factory()->create(['nup' => null]);

        $this->assertSame('Pendente de abertura', $this->statusFor($notice));
    }

    public function test_notice_with_nup_and_no_projects_is_em_abertura(): void
    {
        $notice = Notice::factory()->create();

        $this->assertSame('Processos em andamento', $this->statusFor($notice));
    }

    public function test_notice_with_nup_and_one_project_pagamento_aprovado_is_processos_formalizados(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $project->stages()->where('slug', ProjectStageSlug::PAGAMENTO)
            ->update(['status' => ProjectStageStatus::APROVADO]);

        $this->assertSame('Processos formalizados', $this->statusFor($notice));
    }

    public function test_notice_with_project_em_pagamento_and_not_yet_approved_is_em_abertura(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $project->stages()->where('slug', ProjectStageSlug::PAGAMENTO)
            ->update(['status' => ProjectStageStatus::EM_ANDAMENTO]);

        $this->assertSame('Processos em andamento', $this->statusFor($notice));
    }

    public function test_notice_with_only_one_of_two_projects_pagamento_aprovado_is_em_abertura(): void
    {
        $notice = Notice::factory()->create();

        $projectComPagamentoAprovado = Project::factory()->create(['notice_id' => $notice->id]);
        $projectComPagamentoAprovado->stages()->where('slug', ProjectStageSlug::PAGAMENTO)
            ->update(['status' => ProjectStageStatus::APROVADO]);

        Project::factory()->create(['notice_id' => $notice->id]);

        $this->assertSame('Processos em andamento', $this->statusFor($notice));
    }

    public function test_notice_stays_processos_formalizados_after_installment_cycle_reset(): void
    {
        $notice = Notice::factory()->create(['installments' => 2]);
        $project = Project::factory()->create([
            'notice_id' => $notice->id,
            'current_installment_cycle' => 2,
        ]);

        // Reflete o reset feito por ProjectStageService::requestNextInstallment()
        // ao solicitar a próxima parcela: ORCAMENTO/PAGAMENTO/MONITORAMENTO voltam
        // para pendente/em_andamento, mesmo já tendo havido pagamento no ciclo anterior.
        $project->stages()->where('slug', ProjectStageSlug::ORCAMENTO)
            ->update(['status' => ProjectStageStatus::EM_ANDAMENTO]);
        $project->stages()->where('slug', ProjectStageSlug::PAGAMENTO)
            ->update(['status' => ProjectStageStatus::PENDENTE]);
        $project->stages()->where('slug', ProjectStageSlug::MONITORAMENTO)
            ->update(['status' => ProjectStageStatus::PENDENTE]);

        $this->assertSame('Processos formalizados', $this->statusFor($notice));
    }

    public function test_totals_pendentes_counts_notices_without_nup(): void
    {
        Notice::factory()->create(['nup' => null]);
        Notice::factory()->create(['nup' => null]);
        Notice::factory()->create();

        $totais = app(NoticeService::class)->getTotals();

        $this->assertSame(2, $totais['pendentes']);
    }

    public function test_totals_oportunidades_counts_notices_com_processo_em_andamento(): void
    {
        Notice::factory()->create();
        Notice::factory()->create();
        Notice::factory()->create(['nup' => null]);

        $totais = app(NoticeService::class)->getTotals();

        $this->assertSame(2, $totais['oportunidades']);
    }

    public function test_totals_concluidos_counts_notices_com_processos_formalizados(): void
    {
        $noticeFormalizado = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $noticeFormalizado->id]);
        $project->stages()->where('slug', ProjectStageSlug::PAGAMENTO)
            ->update(['status' => ProjectStageStatus::APROVADO]);

        Notice::factory()->create();
        Notice::factory()->create(['nup' => null]);

        $totais = app(NoticeService::class)->getTotals();

        $this->assertSame(1, $totais['concluidos']);
    }
}
