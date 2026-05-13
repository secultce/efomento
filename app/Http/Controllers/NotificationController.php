<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $service
    ) {}

    public function index(Request $request): Response
    {
        $notifications = $this->service->getUserNotifications(
            user: $request->user(),
            filters: [
                'read' => $request->has('read')
                    ? $request->boolean('read')
                    : null,
                'type' => $request->input('type'),
            ],
            pagination: 5
        );

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function unreadCount(Request $request): array
    {
        return [
            'count' => $this->service->getUnreadCount(
                $request->user()
            ),
        ];
    }

    public function markAsRead(
        Request $request,
        string $id
    ): RedirectResponse {
        $success = $this->service->markAsRead(
            $request->user(),
            $id
        );

        if (!$success) {
            return back()->with(
                'error',
                'Notification not found.'
            );
        }

        return back()->with(
            'success',
            'Notification marked as read.'
        );
    }

    public function markAllAsRead(
        Request $request
    ): RedirectResponse {
        $this->service->markAllAsRead(
            $request->user()
        );

        return back()->with(
            'success',
            'All notifications marked as read.'
        );
    }
}