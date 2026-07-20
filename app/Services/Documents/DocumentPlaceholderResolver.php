<?php

namespace App\Services\Documents;

use App\Models\Document;

class DocumentPlaceholderResolver
{
    public const RELATIONS = [
        'project.agent.latestSnapshot',
        'project.notice',
        'project.opening.principalSupervisor.user',
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

        $replacements = [
            '[notice_name]' => $document->project?->notice?->name,
            '[nup_mother]' => $document->project?->notice?->nup,
            '[project_nup]' => $opening?->opening_nup,
            '[agent_name]' => $document->project?->agent?->name,
            '[agent_cpf]' => $snapshot?->cpf_cnpj,
            '[agent_address]' => $snapshot?->street.', '.$snapshot?->number.' - '.
                $snapshot?->neighborhood.' - '.$snapshot?->city.'/'.$snapshot?->state,
            '[agent_email]' => $snapshot?->email,
            '[agent_phone]' => $snapshot?->phone,
            '[finality]' => $document->project?->notice?->instrument_type,
            '[fiscal_matricula]' => $supervisor?->registration_number,
            '[fiscal_name]' => $supervisor?->name,
            '[project_name]' => $document->project?->title_project,
            '[allocation_code]' => $opening?->allocation_code,
            '[allocation_number]' => $opening?->allocation_number,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $document->body);
    }
}
