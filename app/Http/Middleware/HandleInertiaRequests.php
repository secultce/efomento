<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Route;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user()?->getRoleNames() ?? [],
                'permissions' => $request->user()?->getAllPermissions()->pluck('name') ?? [],
                'currentRoute' => Route::currentRouteName(),
            ],
            'notifications' => function () use ($request) {
                if (!$request->user()) {
                    return [];
                }
                return $request->user()
                    ->unreadNotifications()
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn ($notification) => [
                        'id' => $notification->id,
                        'data' => $notification->data,
                        'created_at' => $notification->created_at,
                    ]);
            },
            'allUnreadCount' => function () use ($request) {
                if (!$request->user()) {
                    return 0;
                }
                return $request->user()
                    ->unreadNotifications()
                    ->count();
            },
        ];
    }
}
