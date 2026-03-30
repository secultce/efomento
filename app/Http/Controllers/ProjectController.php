<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Notice;
use App\Enums\ProjectPhase;
use App\Enums\InstrumentType;

class ProjectController extends Controller
{
    public function index(Request $request, Notice $notice)
    {
        $query = $notice->projects()
            ->with(['agent', 'category', 'opening'])
            ->withCount('openings')
            ->filterPhase($request->phase)
            ->search($request->search);

        return Inertia::render('Projects', [
            'notice' => $notice,
            'projects' => $query->get(),
            'filters' => $request->only(['phase', 'search']),
            'instrumentTypes' => InstrumentType::values(),
            'phases' => collect(ProjectPhase::cases())->map(fn ($phase) => [
                'value' => $phase->value,
                'title' => $phase->label(),
                'total' => $phase->count($query),
            ]),
        ]);
    }

}