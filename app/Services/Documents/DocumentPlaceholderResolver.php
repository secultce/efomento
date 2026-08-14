<?php

namespace App\Services\Documents;

use App\Models\BudgetAllocation;
use App\Models\Document;
use App\Models\Notice;
use App\Models\Project;
use App\Services\BudgetAllocationResolver;

class DocumentPlaceholderResolver
{
    public const RELATIONS = [
        'notice',
        'project.agent.latestSnapshot',
        'project.notice',
        'project.opening.principalSupervisor.user',
        'project.budgets.installments',
        'project.category',
        'project.budgets.installments.budgetAllocation',
    ];

    public function __construct(
        private readonly BudgetAllocationResolver $budgetAllocationResolver,
    ) {}

    public function prepare(Document $document): Document
    {
        $document->resolvedBody = $this->resolve($document);

        return $document;
    }

    public function resolve(Document $document): string
    {
        $document->loadMissing(self::RELATIONS);

        $snapshot = $document->project?->agent?->latestSnapshot;
        $opening = $document->project?->opening;
        $supervisor = $opening?->principalSupervisor?->user;
        $notice = $document->project?->notice ?? $document->notice;
        $currentInstallment = $document->project?->budgets?->installments
            ?->firstWhere('installment_number', $document->project?->current_installment_cycle);
        $budgetAllocation = $document->project
            ? $this->budgetAllocationResolver->resolve($document->project)
            : null;

        $replacements = [
            '[notice_name]' => $notice?->name ?? '',
            '[nup_mother]' => $notice?->nup ?? '',
            '[project_nup]' => $opening?->opening_nup ?? '',
            '[agent_name]' => $document->project?->agent?->name ?? '',
            '[agent_cpf]' => $snapshot?->cpf_cnpj ?? '',
            '[agent_address]' => $snapshot
                ? "{$snapshot->street}, {$snapshot->number} - {$snapshot->neighborhood} - {$snapshot->city}/{$snapshot->state}"
                : '',
            '[agent_email]' => $snapshot?->email ?? '',
            '[agent_phone]' => $snapshot?->phone ?? '',
            '[finality]' => $notice?->instrument_type ?? '',
            '[fiscal_matricula]' => $supervisor?->registration_number ?? '',
            '[fiscal_name]' => $supervisor?->name ?? '',
            '[project_name]' => $document->project?->title_project ?? '',
            '[allocation_code]' => $budgetAllocation?->allocation_code ?? '',
            '[allocation_number]' => $budgetAllocation?->allocation_number ?? '',
            '[notice_installment_number]' => $currentInstallment?->notice_installment_number ?? '',
            '[bank]' => $opening?->bank ?? '',
            '[account_type]' => $opening?->account_type?->label() ?? '',
            '[branch]' => $opening?->branch ?? '',
            '[account]' => $opening?->account ?? '',
            '[budget_allocation_nup]' => $document->project?->notice?->budget_allocation_nup ?? '',
            '[creditor_registration_nup]' => $document->project?->notice?->creditor_registration_nup ?? '',
            '[project_category]' => $document->project?->category?->name ?? '',
        ];

        $body = str_replace(array_keys($replacements), array_values($replacements), (string) $document->body);

        $body = $this->replaceBudgetAllocationData($body, $document->project, $notice);

        return $this->replaceBudgetAllocationsByRegionTable($body, $notice);
    }

    public function replaceBudgetAllocationData(string $content, ?Project $project, ?Notice $notice = null): string
    {
        if (! str_contains($content, '[budget_allocation_data]')) {
            return $content;
        }

        return str_replace(
            '[budget_allocation_data]',
            $this->budgetAllocationData($project, $notice),
            $content,
        );
    }

    public function replaceBudgetAllocationsByRegionTable(string $content, ?Notice $notice): string
    {
        $placeholder = '[budget_allocations_by_region_table]';

        if (! str_contains($content, $placeholder)) {
            return $content;
        }

        if (! $notice) {
            return str_replace($placeholder, '', $content);
        }

        $allocations = $notice->budgetAllocations()
            ->reorder('region_code')
            ->orderBy('id')
            ->get()
            ->filter(fn (BudgetAllocation $allocation) => filled($allocation->planning_macroregion)
                && (filled($allocation->allocation_code) || filled($allocation->allocation_number)));

        if ($allocations->isEmpty()) {
            return str_replace($placeholder, '', $content);
        }

        $rows = $allocations
            ->map(function (BudgetAllocation $allocation) {
                $macroregion = $this->macroregionLabel($allocation);
                $budgetAllocation = collect([
                    $allocation->allocation_code,
                    $allocation->allocation_number,
                ])->filter(fn ($value) => filled($value))
                    ->map(fn ($value) => e(trim((string) $value)))
                    ->implode(' - ');

                return '<tr>'
                    .'<td style="border: 1px solid #9ca3af; padding: 5px 7px; vertical-align: top;">'.e($macroregion).'</td>'
                    .'<td style="border: 1px solid #9ca3af; padding: 5px 7px; vertical-align: top;">'.$budgetAllocation.'</td>'
                    .'</tr>';
            })
            ->implode('');

        $table = '<table style="width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 10px; text-align: left;">'
            .'<thead><tr>'
            .'<th style="width: 34%; border: 1px solid #9ca3af; padding: 6px 7px; background-color: #e6f1e3; text-align: center;">Macrorregião de Planejamento</th>'
            .'<th style="width: 66%; border: 1px solid #9ca3af; padding: 6px 7px; background-color: #e6f1e3; text-align: center;">Dotações</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table>';

        return str_replace($placeholder, $table, $content);
    }

    private function budgetAllocationData(?Project $project, ?Notice $notice): string
    {
        if (! $project && ! $notice) {
            return '';
        }

        $project?->loadMissing('notice.budgetAllocations');
        $notice?->loadMissing('budgetAllocations');

        $allocation = ($project ? $this->budgetAllocationResolver->resolve($project) : null)
            ?? $project?->notice?->budgetAllocations?->sortBy('id')->first()
            ?? $notice?->budgetAllocations?->sortBy('id')->first();

        if (! $allocation) {
            return '';
        }

        return $this->budgetAllocationBlock($allocation);
    }

    private function budgetAllocationBlock(BudgetAllocation $allocation): string
    {
        $fields = [
            'Gestão/Unidade' => $allocation->management_unit,
            'Programa de Trabalho' => $allocation->work_program,
            'Objetivo' => $allocation->objective,
            'Entrega' => $allocation->deliverable,
            'Função' => $allocation->budget_function,
            'Subfunção' => $allocation->budget_subfunction,
            'Ação' => $allocation->project_activity,
            'Elemento de Despesa' => $allocation->expense_element,
            'Fonte de Recursos' => $allocation->funding_source,
            'MAPP' => $allocation->mapp,
            'Projeto Finalístico' => $allocation->finalistic_project,
        ];

        $lines = collect($fields)
            ->map(function ($value, string $label) {
                $escapedValue = nl2br(e((string) ($value ?? '')), false);

                return '<span style="display: block;"><strong>'.e($label).':</strong> '.$escapedValue.'</span>';
            })
            ->implode('');

        return '<span style="display: block; line-height: 1.35; text-align: left;">'.$lines.'</span>';
    }

    private function macroregionLabel(BudgetAllocation $allocation): string
    {
        $code = trim((string) $allocation->region_code);
        $macroregion = trim((string) $allocation->planning_macroregion);

        if ($code === '' || preg_match('/^'.preg_quote($code, '/').'\s*[-–—]/u', $macroregion)) {
            return $macroregion;
        }

        return $code.' – '.$macroregion;
    }
}
