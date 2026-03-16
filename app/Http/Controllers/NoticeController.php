<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Notice\NoticeStoreRequest;
use App\Http\Requests\Notice\NoticeUpdateRequest;
use App\Services\NoticeService;
use App\Enums\InstrumentType;


class NoticeController extends Controller
{
    

    public function __construct(private readonly NoticeService $noticeService) {}

    public function index(Request $request)
    {
        return Inertia::render('Notices/Index', [
            'user' => Auth::user(),
            'oportunidades' => $this->noticeService
                ->getNoticesForDashboard($request->query('search')),
            'totais' => $this->noticeService->getTotals(),
            'instrumentTypes' => InstrumentType::values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NoticeStoreRequest $request)
    {
        Notice::create($request->validated());

        return redirect()
            ->route('notices.index')
            ->with('success', 'Notice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Notice $notice)
    {
        return Inertia::render('Notices/Show', [
            'notice' => $notice
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NoticeUpdateRequest $request, Notice $notice)
    {
        $notice->update($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Notice updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notice $notice)
    {
        $notice->delete();

        return redirect()
            ->route('notices.index')
            ->with('success', 'Notice deleted successfully.');
    }
}