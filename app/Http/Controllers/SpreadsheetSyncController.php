<?php

namespace App\Http\Controllers;

use App\Models\Formalization;
use App\Models\Project;
use App\Services\GoogleSheetsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpreadsheetSyncController extends Controller
{
    public function __construct(
        private readonly GoogleSheetsService $sheets,
    ) {}

    public function syncFormalizacao(Request $request): RedirectResponse
    {
        $request->validate([
            'spreadsheet_id' => ['required', 'string'],
            'sheet_name' => ['sometimes', 'string'],
        ]);

        $config = config('spreadsheet_mappings.formalizacao');
        $columnMap = $config['column_map'];
        $projectLookupColumn = $config['column_for_project_lookup'];

        ['rows' => $rows] = $this->sheets->fetchSheet(
            spreadsheetId: $request->string('spreadsheet_id')->toString(),
            sheetName: $request->string('sheet_name', 'Formalização')->toString(),
        );

        $count = 0;

        foreach ($rows as $row) {
            $projectNumber = $row[$projectLookupColumn] ?? null;

            if (! $projectNumber) {
                continue;
            }

            $project = Project::where('number', $projectNumber)->first();

            if (! $project) {
                continue;
            }

            $record = ['process_supervisor_id' => Auth::id(), 'created_by' => Auth::id()];

            foreach ($columnMap as $sheetColumn => $modelField) {
                $record[$modelField] = $row[$sheetColumn] ?? null;
            }

            Formalization::updateOrCreate(
                ['project_id' => $project->id],
                $record,
            );

            $count++;
        }

        return back()->with('success', "{$count} registros de formalização sincronizados.");
    }
}
