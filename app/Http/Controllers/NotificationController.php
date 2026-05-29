<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $audience = ($user->role === 'admin' || $user->role === 'staff') ? 'admin' : 'customer';

        $notifications = Notification::where('user_id', $user->id)
            ->where('audience', $audience)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn($n) => $this->format($n));

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('audience', $audience)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function markRead(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function format(Notification $n): array
    {
        return [
            'id'        => $n->id,
            'type'      => $n->type,
            'title'     => $n->title,
            'message'   => $n->message,
            'data'      => $n->data,
            'read'      => !is_null($n->read_at),
            'createdAt' => $n->created_at->toIso8601String(),
        ];
    }
}
