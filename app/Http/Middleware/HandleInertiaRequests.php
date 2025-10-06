<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

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
        $user = $request->user();
        $notifications = collect();
        if ($user) {
            $notifications = $user->unreadNotifications()->take(4)->get();
            if ($notifications->count() === 0) {
                $notifications = $user->notifications()->take(4)->get();
            }
            $notifications = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at?->toDateTimeString(),
                ];
            });
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()?->getAllCachedPermissions() ?? [],
                'notifications' => $notifications ?? [],
            ],
            'flash' => function() use($request){
                return [
                    'error' => $request->session()->get('error'),
                    'success' => $request->session()->get('success'),
                    'warning' => $request->session()->get('warning'),
                    'info' => $request->session()->get('info'),
                ];
            }
        ];
    }
}
