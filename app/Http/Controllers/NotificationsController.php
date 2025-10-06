<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate(20);
        $notifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toDateTimeString(),
            ];
        });

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markManyAsRead(Request $request)
    {
        $user = $request->user();
        $ids = $request->input('ids', []);
        $user->notifications()->whereIn('id', $ids)->get()->each->markAsRead();

        return back()->with('success', 'Selected notifications marked as read.');
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('success', 'Notification deleted successfully.');
    }

    public function destroyAll(Request $request)
    {
        $user = $request->user();
        $user->notifications()->delete();

        return back()->with('success', 'All notifications deleted successfully.');
    }
}
