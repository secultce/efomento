<?php

namespace App\Http\Controllers;

use App\Services\OpportunityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly OpportunityService $opportunityService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Editais/Index', [
            'user'     => Auth::user(),
            'oportunidades' => $this->opportunityService->getOpportunitiesForDashboard($request->query('search')),
            'totais'   => $this->opportunityService->getTotals(),
        ]);
    }
}
