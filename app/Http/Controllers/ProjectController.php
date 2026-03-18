<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Notice;

class ProjectController extends Controller
{
    public function index(Request $request, Notice $notice)
    {
        $query = $notice->projects()
            ->with(['agent', 'category']);

        // Stage filter
        if ($request->filled('stage')) {
            $query->where('phase', $request->stage);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('process', 'like', "%{$search}%")
                ->orWhereHas('agent', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            });
        }

        return Inertia::render('Projects', [
            'notice' => $notice,
            'projects' => $query->get(),
            'filters' => $request->only(['stage', 'search']),
        ]);
    }

}