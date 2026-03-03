<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Editais/Index', [
            'user'     => Auth::user(),
            'projetos' => $this->projectService->getProjectsForDashboard($request->query('search')),
            'totais'   => $this->projectService->getTotals(),
        ]);
    }
}
