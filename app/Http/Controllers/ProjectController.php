<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Notice;

class ProjectController extends Controller
{
    public function index(Notice $notice)
    {
        return Inertia::render('Projects', [
            'notice' => $notice,
            'projects' => $notice->projects()
                ->with(['agent', 'category'])
                ->get(),
        ]);
    }

}