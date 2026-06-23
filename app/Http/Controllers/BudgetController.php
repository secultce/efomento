<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budget\BudgetStoreRequest;
use App\Http\Requests\Budget\BudgetUpdateRequest;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function store(BudgetStoreRequest $request, Project $project): RedirectResponse
    {
        DB::transaction(function () use ($request, $project) {
            $budget = Budget::create([
                'project_id' => $project->id,
                'created_by' => auth()->id(),
                'processing_date_for_codip' => $request->processing_date_for_codip,
                'processing_date_for_coafi' => $request->processing_date_for_coafi,
            ]);

            $budget->installments()->create([
                'amount' => $request->installment_amount,
                'request_date' => $request->installment_request_date,
                'justification' => $request->installment_justification,
                'observations' => $request->installment_observations,
                'installment_number' => $project->current_installment_cycle,
                'created_by' => auth()->id(),
            ]);
        });

        return back();
    }

    public function update(BudgetUpdateRequest $request, Project $project, Budget $budget): RedirectResponse
    {
        DB::transaction(function () use ($request, $budget, $project) {
            $cycle = $project->current_installment_cycle;

            if ($cycle === 1) {
                $budget->update([
                    'processing_date_for_codip' => $request->processing_date_for_codip,
                    'processing_date_for_coafi' => $request->processing_date_for_coafi,
                ]);
            }

            $installment = $budget
                ->installments()
                ->where('installment_number', $cycle)
                ->first();

            if ($installment) {
                $installment->update([
                    'amount' => $request->installment_amount,
                    'request_date' => $request->installment_request_date,
                    'justification' => $request->installment_justification,
                    'observations' => $request->installment_observations,
                ]);

                return;
            }

            $budget->installments()->create([
                'amount' => $request->installment_amount,
                'request_date' => $request->installment_request_date,
                'justification' => $request->installment_justification,
                'observations' => $request->installment_observations,
                'installment_number' => $cycle,
                'created_by' => auth()->id(),
            ]);
        });

        return back();
    }
}
