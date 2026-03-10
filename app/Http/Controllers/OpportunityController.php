<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use Inertia\Inertia;
use App\Http\Requests\Opportunity\OpportunityStoreRequest;
use App\Http\Requests\Opportunity\OpportunityUpdateRequest;
use App\Enums\InstrumentType;

class OpportunityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Opportunities/Index', [
            'opportunities' => Opportunity::latest()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OpportunityStoreRequest $request)
    {
        Opportunity::create($request->validated());

        return redirect()
            ->route('opportunities.index')
            ->with('success', 'Opportunity created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Opportunity $opportunity)
    {
        return Inertia::render('Opportunities/Show', [
            'opportunity' => $opportunity
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OpportunityUpdateRequest $request, Opportunity $opportunity)
    {
        $opportunity->update($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Opportunity updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();

        return redirect()
            ->route('opportunities.index')
            ->with('success', 'Opportunity deleted successfully.');
    }
}