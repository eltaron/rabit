<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $userId = Auth::id();

        $query = DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', Auth::user()->getMorphClass());

        if ($request->has('unread_only') && $request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->orderByDesc('created_at')->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->update(['read_at' => now()]);

        return back()->with('success', 'تم وضع علامة مقروء.');
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->where('notifiable_type', Auth::user()->getMorphClass())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'تم وضع علامة مقروء على الكل.');
    }

    public function getUnreadCount(): JsonResponse
    {
        $count = DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->where('notifiable_type', Auth::user()->getMorphClass())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
