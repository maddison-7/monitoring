<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        // Verify notification belongs to authenticated user
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::query()
            ->where('user_id', (int) $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function clearRead(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('user_id', (int) $request->user()->id)
            ->whereNotNull('read_at')
            ->delete();

        return back()->with('success', 'Read notifications cleared.');
    }
}
