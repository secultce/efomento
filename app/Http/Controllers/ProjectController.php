<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Notice;
use App\Enums\ProjectPhase;

class ProjectController extends Controller
{
    public function index(Request $request, Notice $notice)
    {
        $query = $notice->projects()
            ->with(['agent', 'category', 'opening'])
            ->withCount('openings');

        if ($request->filled('phase')) {
            $phase = ProjectPhase::fromRequest($request->phase);

            if ($phase) {
                $phase->applyFilter($query);
            }
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);

            $query->where(function ($q) use ($search) {

                $q->orWhereHas('agent', function ($q2) use ($search) {
                    $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                });

                $q->orWhereHas('openings', function ($q3) use ($search) {
                    $q3->whereRaw('LOWER(opening_nup) LIKE ?', ["%{$search}%"]);
                });

            });
        }

        return Inertia::render('Projects', [
            'notice' => $notice,
            'projects' => $query->get(),
            'filters' => $request->only(['phase', 'search']),
            'phases' => collect(ProjectPhase::cases())->map(fn ($phase) => [
                'value' => $phase->value,
                'title' => $phase->label(),
                'total' => $phase->count($query),
            ]),
        ]);
    }

}