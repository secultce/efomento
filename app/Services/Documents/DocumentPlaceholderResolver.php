<?php

namespace App\Services\Documents;

use App\Models\BudgetAllocation;
use App\Models\Document;
use App\Models\Project;

class DocumentPlaceholderResolver
{
    public const RELATIONS = [
        'project.agent.latestSnapshot',
        'project.notice',
        'project.opening.principalSupervisor.user',
        'project.budgets.installments',
    ];

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
        $currentInstallment = $document->project?->budgets?->installments
            ?->firstWhere('installment_number', $document->project?->current_installment_cycle);

        $replacements = [
            '[notice_name]' => $document->project?->notice?->name ?? '',
            '[nup_mother]' => $document->project?->notice?->nup ?? '',
            '[project_nup]' => $opening?->opening_nup ?? '',
            '[agent_name]' => $document->project?->agent?->name ?? '',
            '[agent_cpf]' => $snapshot?->cpf_cnpj ?? '',
            '[agent_address]' => $snapshot
                ? "{$snapshot->street}, {$snapshot->number} - {$snapshot->neighborhood} - {$snapshot->city}/{$snapshot->state}"
                : '',
            '[agent_email]' => $snapshot?->email ?? '',
            '[agent_phone]' => $snapshot?->phone ?? '',
            '[finality]' => $document->project?->notice?->instrument_type ?? '',
            '[fiscal_matricula]' => $supervisor?->registration_number ?? '',
            '[fiscal_name]' => $supervisor?->name ?? '',
            '[project_name]' => $document->project?->title_project ?? '',
            '[allocation_code]' => $opening?->allocation_code ?? '',
            '[allocation_number]' => $opening?->allocation_number ?? '',
            '[notice_installment_number]' => $currentInstallment?->notice_installment_number ?? '',
        ];

        $body = str_replace(array_keys($replacements), array_values($replacements), (string) $document->body);

        return $this->replaceBudgetAllocationData($body, $document->project);
    }

    public function replaceBudgetAllocationData(string $content, ?Project $project): string
    {
        if (! str_contains($content, '[budget_allocation_data]')) {
            return $content;
        }

        return str_replace(
            '[budget_allocation_data]',
            $this->budgetAllocationData($project),
            $content,
        );
    }

    private function budgetAllocationData(?Project $project): string
    {
        if (! $project) {
            return '';
        }

        $project->loadMissing([
            'budgets.budgetAllocation',
            'notice.budgetAllocations',
        ]);

        $allocation = $project->budgets?->budgetAllocation
            ?? $project->notice?->budgetAllocations?->sortBy('id')->first();

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
}
