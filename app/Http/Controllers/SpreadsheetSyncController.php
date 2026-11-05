<?php

namespace App\Http\Controllers;

use App\Http\Requests\Spreadsheet\ImportGoogleSheetsRequest;
use App\Jobs\ImportGoogleSheetsJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SpreadsheetSyncController extends Controller
{
    public function sync(ImportGoogleSheetsRequest $request): RedirectResponse
    {
        ImportGoogleSheetsJob::dispatch(
            $request->spreadsheetId(),
            (int) $request->validated('user_id'),
            $request->filled('fallback_notice_id') ? (int) $request->validated('fallback_notice_id') : null,
            $request->user()->id,
        );

        return back()->with('success', 'Sincronização iniciada com sucesso em segundo plano. Você receberá uma notificação ao término.');
    }

    public function usersList(): JsonResponse
    {
        return response()->json(User::query()->with('avatarFile')->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'avatar' => $user->avatarUrl()]));
    }
}
