<?php

namespace App\Http\Controllers;

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

        $count = $this->sheets->syncFormalization(
            spreadsheetId: $request->string('spreadsheet_id')->toString(),
            sheetName: $request->string('sheet_name', 'Formalização')->toString(),
            userId: Auth::id(),
        );

        return back()->with('success', "{$count} registros de formalização sincronizados.");
    }

    public function syncOrcamento(Request $request): RedirectResponse
    {
        $request->validate([
            'spreadsheet_id' => ['required', 'string'],
            'sheet_name' => ['sometimes', 'string'],
        ]);

        $count = $this->sheets->syncBudget(
            spreadsheetId: $request->string('spreadsheet_id')->toString(),
            sheetName: $request->string('sheet_name', 'Orçamento')->toString(),
            userId: Auth::id(),
        );

        return back()->with('success', "{$count} registros de orçamento sincronizados.");
    }
}
