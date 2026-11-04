<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackPageView
{
    /**
     * Rutas internas, assets y endpoints que no cuentan como vistas de página.
     *
     * @var array<int, string>
     */
    private const EXCLUDED_PREFIXES = [
        'admin',
        'api',
        'build',
        'images',
        'storage',
        'media',
        'up',
        '_ignition',
        'livewire',
        'telescope',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Registra la vista después de enviar la respuesta al usuario.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $this->shouldTrack($request, $response)) {
                return;
            }

            PageView::create([
                'path' => $this->normalizedPath($request),
                'route_name' => $request->route()?->getName(),
                'user_id' => Auth::id(),
                'session_hash' => $request->hasSession() ? hash('sha256', $request->session()->getId()) : null,
                'ip_hash' => hash('sha256', (string) $request->ip()),
                'device' => $this->device($request->userAgent()),
                'referrer' => Str::limit((string) $request->headers->get('referer'), 1000, ''),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'viewed_at' => now(),
            ]);
        } catch (Throwable) {
            // La analítica nunca debe interrumpir la navegación del usuario.
        }
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if ($request->method() !== 'GET' || $response->getStatusCode() >= 400) {
            return false;
        }

        // Solo visitas de clientes e invitados: el panel interno no cuenta.
        $user = Auth::user();
        if ($user && in_array($user->role, ['Admin', 'Empleado'], true)) {
            return false;
        }

        // Ignoramos las precargas de Inertia y los rastreadores.
        if (strtolower((string) $request->headers->get('Purpose')) === 'prefetch') {
            return false;
        }

        $agent = strtolower((string) $request->userAgent());
        if ($agent === '' || preg_match('/bot|crawl|spider|slurp|bingpreview|headless/i', $agent)) {
            return false;
        }

        $path = trim($request->path(), '/');

        if ($path !== '') {
            foreach (self::EXCLUDED_PREFIXES as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                    return false;
                }
            }
        }

        return ! preg_match('/\.(css|js|mjs|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf|zip)$/i', $path);
    }

    private function normalizedPath(Request $request): string
    {
        $path = trim($request->path(), '/');

        return $path === '' ? '/' : '/' . $path;
    }

    private function device(?string $agent): string
    {
        $agent = strtolower((string) $agent);

        if (preg_match('/ipad|tablet|kindle|playbook|silk/i', $agent)) {
            return 'tablet';
        }

        if (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile/i', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
