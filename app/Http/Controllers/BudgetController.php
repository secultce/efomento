<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budget\BudgetStoreRequest;
use App\Http\Requests\Budget\BudgetUpdateRequest;
use App\Models\Budget;
use App\Models\Project;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService
    ) {}

    public function store(BudgetStoreRequest $request, Project $project): RedirectResponse
    {
        $this->budgetService->create(
            $project,
            $request->validated()
        );

        return back();
    }

    public function update(BudgetUpdateRequest $request, Project $project, Budget $budget): RedirectResponse
    {
        $this->budgetService->update(
            $project,
            $budget,
            $request->validated()
        );

        return back();
    }
}
