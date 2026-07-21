<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Resources\ProjectResource;
use App\Models\Agent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Agent $agent)
    {
        $agent->load('latestSnapshot');

        $projects = $agent->projects()
            ->with([
                'notice',
                'category',
                'opening',
                'currentStage',
                'budgets.installments',
            ])
            ->latest()
            ->get();

        $participations = ProjectResource::collection($projects)->resolve();

        foreach ($participations as $index => $participation) {
            $participations[$index]['received_amount'] = $projects[$index]
                ->budgets?->installments
                ?->sum(fn ($installment) => (float) ($installment->payment_amount ?? 0)) ?? 0;
        }

        $sourceProject = $projects->firstWhere('id', $request->integer('project'));

        return Inertia::render('Agents/Participations', [
            'agent' => $agent,
            'participations' => $participations,
            'accountTypes' => AccountType::options(),
            'backUrl' => $sourceProject
                ? route('notices.projects.show', [$sourceProject->notice_id, $sourceProject->id], absolute: false)
                : route('notices.index', absolute: false),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agent $agent)
    {
        //
    }
}
