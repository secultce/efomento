<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Installment;
use App\Models\Notice;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InstallmentController extends Controller
{
    public function import(Request $request, Notice $notice): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'installment' => ['required', 'integer'],
        ]);

        $sheets = Excel::toArray([], $request->file('file'));

        $rows = $sheets[0] ?? [];

        if (count($rows) < 3) {
            return response()->json([
                'message' => 'Spreadsheet is empty.',
                'installment' => $request->installment,
                'rows' => [],
            ], 422);
        }

        /*
         * Row index 1 contains the headers.
         */
        $headers = collect($rows[1])
            ->map(fn ($header) => Str::snake(trim((string) $header)))
            ->toArray();

        /*
         * Convert spreadsheet rows into objects.
         */
        $data = collect(array_slice($rows, 2))
            ->filter(function ($row) {
                return collect($row)
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->isNotEmpty();
            })
            ->map(function ($row) use ($headers) {
                return array_combine(
                    $headers,
                    array_pad($row, count($headers), null)
                );
            })
            ->values();

        /*
         * Load projects for this notice indexed by normalized NUP.
         */
        $projects = Project::where('notice_id', $notice->id)
            ->with('openings')
            ->get()
            ->mapWithKeys(function ($project) {
                $openingNup = $project->openings?->first()?->opening_nup;

                if (! $openingNup) {
                    return [];
                }

                $normalizedNup = preg_replace(
                    '/\D+/',
                    '',
                    (string) $openingNup
                );

                return [$normalizedNup => $project];
            });

        /*
         * Match spreadsheet rows to projects.
         */
        $data = $data
            ->map(function ($row) use ($projects) {
                $normalizedProcesso = preg_replace(
                    '/\D+/',
                    '',
                    (string) ($row['processo'] ?? '')
                );

                $project = $projects->get($normalizedProcesso);

                if (! $project) {
                    return null;
                }

                return [
                    ...$row,
                    'project_id' => $project->id,
                ];
            })
            ->filter()
            ->values();

        $imported = 0;

        DB::transaction(function () use (
            $data,
            $request,
            &$imported
        ) {
            foreach ($data as $row) {
                $budget = Budget::where(
                    'project_id',
                    $row['project_id']
                )->first();

                if (! $budget) {
                    continue;
                }

                Installment::updateOrCreate(
                    [
                        'budget_id' => $budget->id,
                        'installment_number' => (int) $request->installment,
                    ],
                    [
                        'amount' => $row['liquidado'] ?? 0,

                        'settlement_number' => $row['nota_de_liquidação'] ?? null,

                        'payment_order_number' => $row['ordem_bancária'] ?? null,

                        'payment_amount' => $row['pago'] ?? 0,

                        'commitment_date' => ! empty($row['data_n_l'])
                            ? Date::excelToDateTimeObject(
                                $row['data_n_l']
                            )
                            : null,

                        'payment_date' => ! empty($row['data_o_b'])
                            ? Date::excelToDateTimeObject(
                                $row['data_o_b']
                            )
                            : null,

                        'remarks' => json_encode(
                            $row,
                            JSON_UNESCAPED_UNICODE
                        ),
                    ]
                );

                $imported++;
            }
        });

        return response()->json([
            'message' => 'Installments imported successfully.',
            'installment' => $request->installment,
            'matched_rows' => $data->count(),
            'imported_rows' => $imported,
        ]);
    }
}
