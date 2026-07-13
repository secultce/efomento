<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\InstallmentImportService;
use App\Services\InstallmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function import(
        Request $request,
        InstallmentImportService $service
    ): RedirectResponse {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $result = $service->import(
                file: $request->file('file')
            );

            if ($result['updated'] === 0) {
                return back()->with(
                    'error',
                    'Nenhuma parcela foi atualizada. Verifique se os projetos possuem orçamento e parcela pendente de pagamento.'
                );
            }

            $message = "Importação concluída. {$result['updated']} projeto(s) tiveram parcela(s) atualizada(s) com sucesso.";

            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} projeto(s) foram ignorados por não possuírem orçamento, parcela cadastrada ou parcela pendente de pagamento.";
            }

            return back()->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Ocorreu um erro inesperado ao processar a planilha. Tente novamente ou contate o suporte.'
            );
        }
    }

    public function updateRemark(
        Request $request,
        Project $project,
        int $installment,
        InstallmentService $service
    ): RedirectResponse {
        $data = $request->validate([
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $service->update(
            project: $project,
            installment: $installment,
            remarks: $data['remarks'] ?? null,
        );

        return back()->with('success', 'Observação atualizada com sucesso.');
    }
}
