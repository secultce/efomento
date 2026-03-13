<?php

namespace App\Http\Controllers;

use App\Services\NoticeService;
use App\Enums\InstrumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly NoticeService $noticeService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Editais/Index', [
            'user'     => Auth::user(),
            'oportunidades' => $this->noticeService->getNoticesForDashboard($request->query('search')),
            'totais'   => $this->noticeService->getTotals(),
            'instrumentTypes' => InstrumentType::values(),
        ]);
    }
}
