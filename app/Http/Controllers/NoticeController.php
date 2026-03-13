<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Inertia\Inertia;
use App\Http\Requests\Notice\NoticeStoreRequest;
use App\Http\Requests\Notice\NoticeUpdateRequest;
use App\Enums\InstrumentType;

class NoticeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Notices/Index', [
            'notices' => Notice::latest()->get(),
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