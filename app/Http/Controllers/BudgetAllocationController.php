<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exceptions\AppException;
use App\Models\Notice;
use App\Services\BudgetAllocationImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetAllocationController extends Controller
{
    public function preview(
        Request $request,
        Notice $notice,
        BudgetAllocationImportService $service
    ): JsonResponse {
        $this->authorizeImport($request);
        $this->validateFile($request);

        try {
            return response()->json($service->preview(
                file: $request->file('file'),
                notice: $notice,
            ));
        } catch (AppException $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getHttpStatus());
        }
    }

    public function import(
        Request $request,
        Notice $notice,
        BudgetAllocationImportService $service
    ): JsonResponse {
        $this->authorizeImport($request);
        $this->validateFile($request);

        try {
            return response()->json($service->import(
                file: $request->file('file'),
                notice: $notice,
            ));
        } catch (AppException $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getHttpStatus());
        }
    }

    private function authorizeImport(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(Role::budgetRoles()), 403);
    }

    private function validateFile(Request $request): void
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ], [
            'file.required' => 'Selecione um arquivo CSV.',
            'file.mimes' => 'O arquivo deve estar no formato CSV.',
        ]);
    }
}
