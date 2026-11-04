<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'booking' => fn () => $request->session()->get('booking'),
            ],
            'notifications' => fn () => $request->user()
                ? $request->user()
                    ->userNotifications()
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get()
                    ->map(fn ($notification) => [
                        'id' => $notification->id,
                        'icon' => $notification->icon,
                        'tone' => $notification->tone,
                        'title' => $notification->title,
                        'text' => $notification->text,
                        'url' => $notification->url,
                        'read' => $notification->read_at !== null,
                        'time' => $notification->created_at?->locale('es')->diffForHumans(),
                    ])
                    ->values()
                : [],
            'unreadNotifications' => fn () => $request->user()
                ? $request->user()->userNotifications()->whereNull('read_at')->count()
                : 0,
        ];
    }
}
