<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markRead(UserNotification $notification)
    {
        $this->authorizeOwnership($notification);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back();
    }

    public function markMany(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        UserNotification::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['ids'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    public function markAll()
    {
        UserNotification::query()
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    public function destroy(UserNotification $notification)
    {
        $this->authorizeOwnership($notification);

        $notification->delete();

        return back();
    }

    public function destroyMany(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        UserNotification::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['ids'])
            ->delete();

        return back();
    }

    private function authorizeOwnership(UserNotification $notification): void
    {
        abort_unless($notification->user_id === Auth::id(), 403);
    }
}
