<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Services\BudgetAllocationResolver;

class DocumentPlaceholderResolver
{
    public const RELATIONS = [
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
        $currentInstallment = $document->project?->budgets?->installments
            ?->firstWhere('installment_number', $document->project?->current_installment_cycle);
        $budgetAllocation = $document->project
            ? $this->budgetAllocationResolver->resolve($document->project)
            : null;

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

        return str_replace(array_keys($replacements), array_values($replacements), (string) $document->body);
    }
}
