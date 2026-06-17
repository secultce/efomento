<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Services\InstallmentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function import(
        Request $request,
        Notice $notice,
        InstallmentImportService $service
    ): RedirectResponse {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'installment' => ['required', 'integer', 'min:1', 'max:'.$notice->installments],
            'selectedProjects' => ['required', 'array'],
            'selectedProjects.*' => ['integer'],
        ]);

        try {
            $result = $service->import(
                file: $request->file('file'),
                notice: $notice,
                installment: (int) $request->installment,
                selectedProjects: $request->selectedProjects,
            );

            if ($result['updated'] === 0) {
                return back()->with(
                    'error',
                    'Nenhuma parcela foi atualizada. Verifique se os projetos possuem orçamento e parcela cadastrada.'
                );
            }

            $message = "Importação concluída. {$result['updated']} projeto(s) tiveram a parcela {$result['installment']} atualizada com sucesso.";

            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} projeto(s) foram ignorados por não possuírem orçamento ou parcela cadastrada.";
            }

            return back()->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Ocorreu um erro inesperado ao processar a planilha. Tente novamente ou contate o suporte.'
            );
        }
    }
}
