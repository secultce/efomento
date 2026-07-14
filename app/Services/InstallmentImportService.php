<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Installment;
use App\Models\Project;
use App\Support\Import;
use App\Support\Spreadsheet\Maps\InstallmentSpreadsheetMap;
use App\Support\Spreadsheet\SpreadsheetImporter;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstallmentImportService
{
    private const STATUS_PENDING = 'pending';

    private const STATUS_PAID_REGULAR = 'paid_regular';

    private const STATUS_IRREGULAR = 'irregular';

    public function import(
        UploadedFile $file,
        array $selectedProjectIds = []
    ): array {
        $data = SpreadsheetImporter::import(
            file: $file,
            mapping: InstallmentSpreadsheetMap::definition(),
            required: InstallmentSpreadsheetMap::required(),
        );

        if ($data->isEmpty()) {
            throw new \InvalidArgumentException(
                'A planilha enviada está vazia.'
            );
        }

        $projectsQuery = Project::query()
            ->with('openings')
            ->whereHas('openings');

        if ($selectedProjectIds !== []) {
            $projectsQuery->whereIn('id', $selectedProjectIds);
        }

        $projects = $projectsQuery->get();

        $projectsByNup = $this->buildProjectsByNup($projects);

        if ($projectsByNup->isEmpty()) {
            throw new \InvalidArgumentException(
                'Nenhum projeto possui processo vinculado para comparação com a planilha.'
            );
        }

        $rows = $this->prepareRows($data, $projectsByNup);

        $summary = [
            'processed' => $data->count(),
            'matched' => $rows->whereNotNull('project_id')->count(),
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,

            'regular_updated' => 0,
            'pending_updated' => 0,
            'irregular_updated' => 0,

            'pending_after_update' => 0,
            'irregular_after_update' => 0,
            'paid_regular_after_update' => 0,

            'nups_not_found' => 0,
            'missing_payment_dates' => 0,
            'budgets_not_found' => 0,
            'installments_not_found' => 0,
            'already_paid_regular' => 0,
            'invalid_chronological_dates' => 0,

            'installments' => [],
            'ignored' => [],
        ];

        DB::transaction(function () use ($rows, &$summary) {
            $installmentsCache = [];
            $budgetsCache = [];

            foreach ($rows as $rowIndex => $row) {
                $spreadsheetRow = $row['spreadsheet_row'];

                if (! $row['normalized_nup']) {
                    $this->skip(
                        summary: $summary,
                        row: $spreadsheetRow,
                        reason: 'NUP/processo não informado ou inválido.',
                        counter: 'nups_not_found',
                        nup: $row['processo'] ?? null,
                    );

                    continue;
                }

                if (! $row['project_id']) {
                    $this->skip(
                        summary: $summary,
                        row: $spreadsheetRow,
                        reason: 'NUP não encontrado entre os projetos disponíveis.',
                        counter: 'nups_not_found',
                        nup: $row['processo'] ?? null,
                    );

                    continue;
                }

                $importedPaymentDate = Import::date(
                    $row['data_ob'] ?? null
                );

                if (! $importedPaymentDate) {
                    $this->skip(
                        summary: $summary,
                        row: $spreadsheetRow,
                        reason: 'Data de pagamento não informada ou inválida.',
                        counter: 'missing_payment_dates',
                        nup: $row['processo'] ?? null,
                        projectId: $row['project_id'],
                    );

                    continue;
                }

                $projectId = $row['project_id'];

                if (! array_key_exists($projectId, $budgetsCache)) {
                    $budgetsCache[$projectId] = Budget::query()
                        ->where('project_id', $projectId)
                        ->lockForUpdate()
                        ->first();
                }

                /** @var Budget|null $budget */
                $budget = $budgetsCache[$projectId];

                if (! $budget) {
                    $this->skip(
                        summary: $summary,
                        row: $spreadsheetRow,
                        reason: 'Projeto não possui orçamento cadastrado.',
                        counter: 'budgets_not_found',
                        nup: $row['processo'] ?? null,
                        projectId: $projectId,
                    );

                    continue;
                }

                if (! array_key_exists($budget->id, $installmentsCache)) {
                    $installmentsCache[$budget->id] = Installment::query()
                        ->where('budget_id', $budget->id)
                        ->orderBy('installment_number')
                        ->lockForUpdate()
                        ->get();
                }

                /** @var Collection<int, Installment> $installments */
                $installments = $installmentsCache[$budget->id];

                if ($installments->isEmpty()) {
                    $this->skip(
                        summary: $summary,
                        row: $spreadsheetRow,
                        reason: 'Orçamento não possui parcelas cadastradas.',
                        counter: 'installments_not_found',
                        nup: $row['processo'] ?? null,
                        projectId: $projectId,
                    );

                    continue;
                }

                $sameDateInstallment = $this->findInstallmentByPaymentDate(
                    installments: $installments,
                    paymentDate: $importedPaymentDate,
                );

                if ($sameDateInstallment) {
                    $currentStatus = $this->getInstallmentStatus(
                        $sameDateInstallment
                    );

                    if ($currentStatus === self::STATUS_PAID_REGULAR) {
                        $this->skip(
                            summary: $summary,
                            row: $spreadsheetRow,
                            reason: 'Já existe uma parcela paga e regular com essa data de pagamento.',
                            counter: 'already_paid_regular',
                            nup: $row['processo'] ?? null,
                            projectId: $projectId,
                            installmentNumber: $sameDateInstallment->installment_number,
                        );

                        continue;
                    }

                    if (! $this->respectsInstallmentDateOrder(
                        candidate: $sameDateInstallment,
                        importedPaymentDate: $importedPaymentDate,
                        installments: $installments,
                    )) {
                        $this->skip(
                            summary: $summary,
                            row: $spreadsheetRow,
                            reason: 'Data de pagamento viola a ordem cronológica das parcelas.',
                            counter: 'invalid_chronological_dates',
                            nup: $row['processo'] ?? null,
                            projectId: $projectId,
                            installmentNumber: $sameDateInstallment->installment_number,
                        );

                        continue;
                    }

                    $this->updateInstallment(
                        installment: $sameDateInstallment,
                        row: $row,
                        paymentDate: $importedPaymentDate,
                        previousStatus: $currentStatus,
                        summary: $summary,
                    );

                    continue;
                }

                $candidate = $installments
                    ->sortBy('installment_number')
                    ->first(function (Installment $installment) {
                        return $this->getInstallmentStatus($installment)
                            !== self::STATUS_PAID_REGULAR;
                    });

                if (! $candidate) {
                    $this->skip(
                        summary: $summary,
                        row: $spreadsheetRow,
                        reason: 'Todas as parcelas do projeto já estão pagas e regulares.',
                        counter: 'installments_not_found',
                        nup: $row['processo'] ?? null,
                        projectId: $projectId,
                    );

                    continue;
                }

                if (! $this->respectsInstallmentDateOrder(
                    candidate: $candidate,
                    importedPaymentDate: $importedPaymentDate,
                    installments: $installments,
                )) {
                    $this->skip(
                        summary: $summary,
                        row: $spreadsheetRow,
                        reason: 'Data de pagamento viola a ordem cronológica das parcelas.',
                        counter: 'invalid_chronological_dates',
                        nup: $row['processo'] ?? null,
                        projectId: $projectId,
                        installmentNumber: $candidate->installment_number,
                    );

                    continue;
                }

                $previousStatus = $this->getInstallmentStatus($candidate);

                $this->updateInstallment(
                    installment: $candidate,
                    row: $row,
                    paymentDate: $importedPaymentDate,
                    previousStatus: $previousStatus,
                    summary: $summary,
                );
            }
        });

        $summary['installments'] = collect($summary['installments'])
            ->unique()
            ->sort()
            ->values()
            ->all();

        $summary['imported'] = $summary['updated'];

        return $summary;
    }

    private function buildProjectsByNup(Collection $projects): Collection
    {
        $projectsByNup = collect();

        foreach ($projects as $project) {
            foreach ($project->openings ?? [] as $opening) {
                $normalizedNup = Import::normalizeNup(
                    $opening->opening_nup ?? null
                );

                if ($normalizedNup) {
                    $projectsByNup->put($normalizedNup, $project);
                }
            }
        }

        return $projectsByNup;
    }

    private function prepareRows(
        Collection $data,
        Collection $projectsByNup
    ): Collection {
        return $data
            ->values()
            ->map(function ($originalRow, int $index) use ($projectsByNup) {
                $row = collect($originalRow)->toArray();

                $normalizedNup = Import::normalizeNup(
                    $row['processo'] ?? null
                );

                $project = $normalizedNup
                    ? $projectsByNup->get($normalizedNup)
                    : null;

                $paymentDate = Import::date($row['data_ob'] ?? null);

                return array_merge($row, [
                    'spreadsheet_row' => $index + 2,
                    'project_id' => $project?->id,
                    'normalized_nup' => $normalizedNup,
                    'sort_payment_date' => $paymentDate
                        ? Import::dateKey($paymentDate)
                        : '9999-12-31',
                ]);
            })
            ->sortBy([
                ['normalized_nup', 'asc'],
                ['sort_payment_date', 'asc'],
            ])
            ->values();
    }

    private function findInstallmentByPaymentDate(
        Collection $installments,
        mixed $paymentDate
    ): ?Installment {
        $paymentDateKey = Import::dateKey($paymentDate);

        return $installments
            ->sortBy('installment_number')
            ->first(function (Installment $installment) use ($paymentDateKey) {
                if (! $installment->payment_date) {
                    return false;
                }

                return Import::dateKey($installment->payment_date)
                    === $paymentDateKey;
            });
    }

    private function getInstallmentStatus(
        Installment $installment
    ): string {
        $values = [
            $installment->amount,
            $installment->committed_amount,
            $installment->settlement_amount,
            $installment->payment_amount,
        ];

        $hasMissingValue = collect($values)->contains(
            fn ($value) => $value === null || $value === ''
        );

        if ($hasMissingValue) {
            return self::STATUS_PENDING;
        }

        $normalizedValues = collect($values)
            ->map(fn ($value) => Import::normalizeMoneyForComparison($value))
            ->unique();

        if ($normalizedValues->count() === 1) {
            return self::STATUS_PAID_REGULAR;
        }

        return self::STATUS_IRREGULAR;
    }

    private function respectsInstallmentDateOrder(
        Installment $candidate,
        mixed $importedPaymentDate,
        Collection $installments
    ): bool {
        $importedDate = Carbon::parse($importedPaymentDate)->startOfDay();

        $previousInstallment = $installments
            ->filter(
                fn (Installment $installment) => $installment->installment_number
                    < $candidate->installment_number && $installment->payment_date
            )
            ->sortByDesc('installment_number')
            ->first();

        $nextInstallment = $installments
            ->filter(
                fn (Installment $installment) => $installment->installment_number
                    > $candidate->installment_number && $installment->payment_date
            )
            ->sortBy('installment_number')
            ->first();

        if ($previousInstallment?->payment_date) {
            $previousDate = Carbon::parse(
                $previousInstallment->payment_date
            )->startOfDay();

            if ($importedDate->lessThanOrEqualTo($previousDate)) {
                return false;
            }
        }

        if ($nextInstallment?->payment_date) {
            $nextDate = Carbon::parse(
                $nextInstallment->payment_date
            )->startOfDay();

            if ($importedDate->greaterThanOrEqualTo($nextDate)) {
                return false;
            }
        }

        return true;
    }

    private function updateInstallment(
        Installment $installment,
        array $row,
        mixed $paymentDate,
        string $previousStatus,
        array &$summary
    ): void {
        $installment->update([
            'settlement_date' => Import::date($row['data_nl'] ?? null)
                ?? $installment->settlement_date,

            'settlement_number' => Import::string(
                $row['nota_de_liquidacao'] ?? null
            ) ?? $installment->settlement_number,

            'settlement_amount' => Import::money(
                $row['liquidado'] ?? null
            ) ?? $installment->settlement_amount,

            'payment_date' => $paymentDate,

            'payment_order_number' => Import::string(
                $row['ordem_bancaria'] ?? null
            ) ?? $installment->payment_order_number,

            'full_source' => Import::string(
                $row['fonte_completa'] ?? null
            ) ?? $installment->full_source,

            'expense_nature' => Import::string(
                $row['natureza_despesa'] ?? null
            ) ?? $installment->expense_nature,

            'process_number' => Import::string(
                $row['processo'] ?? null
            ) ?? $installment->process_number,

            'creditor' => Import::string(
                $row['credor'] ?? null
            ) ?? $installment->creditor,

            'creditor_name' => Import::string(
                $row['nome_credor'] ?? null
            ) ?? $installment->creditor_name,

            'retention_creditor' => Import::string(
                $row['credor_retencao'] ?? null
            ) ?? $installment->retention_creditor,

            'origin_bank_domicile' => Import::string(
                $row['domicilio_bancario_origem'] ?? null
            ) ?? $installment->origin_bank_domicile,

            'committed_amount' => Import::money(
                $row['empenhado'] ?? null
            ) ?? $installment->committed_amount,

            'payment_amount' => Import::money(
                $row['pago'] ?? null
            ) ?? $installment->payment_amount,

            'updated_by' => auth()->id(),
        ]);

        $installment->refresh();

        $newStatus = $this->getInstallmentStatus($installment);

        $summary['updated']++;
        $summary['installments'][] = $installment->installment_number;

        match ($previousStatus) {
            self::STATUS_PENDING => $summary['pending_updated']++,
            self::STATUS_IRREGULAR => $summary['irregular_updated']++,
            self::STATUS_PAID_REGULAR => $summary['regular_updated']++,
            default => null,
        };

        match ($newStatus) {
            self::STATUS_PENDING => $summary['pending_after_update']++,
            self::STATUS_IRREGULAR => $summary['irregular_after_update']++,
            self::STATUS_PAID_REGULAR => $summary['paid_regular_after_update']++,
            default => null,
        };
    }

    private function skip(
        array &$summary,
        int $row,
        string $reason,
        string $counter,
        mixed $nup = null,
        ?int $projectId = null,
        ?int $installmentNumber = null
    ): void {
        $summary['skipped']++;

        if (array_key_exists($counter, $summary)) {
            $summary[$counter]++;
        }

        $summary['ignored'][] = [
            'row' => $row,
            'nup' => $nup,
            'project_id' => $projectId,
            'installment_number' => $installmentNumber,
            'reason' => $reason,
        ];
    }
}
