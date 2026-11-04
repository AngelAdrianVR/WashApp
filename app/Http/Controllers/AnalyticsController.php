<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    /**
     * Analítica de visitas del sitio (solo administradores).
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'granularity' => ['nullable', Rule::in(['auto', 'day', 'week', 'month'])],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $spanDays = (int) $from->diffInDays($to) + 1;
        $granularity = $validated['granularity'] ?? 'auto';

        if ($granularity === 'auto') {
            $granularity = match (true) {
                $spanDays <= 45 => 'day',
                $spanDays <= 180 => 'week',
                default => 'month',
            };
        }

        $baseQuery = PageView::query()->whereBetween('viewed_at', [$from, $to]);

        $views = (clone $baseQuery)->count();
        $visitors = (clone $baseQuery)->whereNotNull('session_hash')->distinct()->count('session_hash');
        $pages = (clone $baseQuery)->distinct()->count('path');

        $busiestDay = (clone $baseQuery)
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as views')
            ->groupByRaw('DATE(viewed_at)')
            ->orderByDesc('views')
            ->first();

        // Comparativo contra el periodo inmediatamente anterior de la misma duración.
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($spanDays - 1)->startOfDay();
        $previousQuery = PageView::query()->whereBetween('viewed_at', [$previousFrom, $previousTo]);
        $previousViews = (clone $previousQuery)->count();
        $previousVisitors = (clone $previousQuery)->whereNotNull('session_hash')->distinct()->count('session_hash');

        return Inertia::render('Admin/Analytics/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'granularity' => $granularity,
            ],
            'summary' => [
                'views' => $views,
                'visitors' => $visitors,
                'pages' => $pages,
                'viewsPerVisitor' => $visitors > 0 ? round($views / $visitors, 2) : 0,
                'viewsChange' => $this->percentageChange($views, $previousViews),
                'visitorsChange' => $this->percentageChange($visitors, $previousVisitors),
                'previousViews' => $previousViews,
                'previousVisitors' => $previousVisitors,
                'busiestDay' => $busiestDay?->day,
                'busiestDayViews' => (int) ($busiestDay?->views ?? 0),
            ],
            'timeseries' => $this->timeseries($from, $to, $granularity),
            'topPages' => $this->topPages($baseQuery, $views),
            'devices' => $this->devices($baseQuery, $views),
            'referrers' => $this->referrers($baseQuery, $views, $request),
            'generatedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * Serie temporal de vistas y visitantes únicos, rellenando los periodos sin datos.
     *
     * @return array{labels: array<int, string>, views: array<int, int>, visitors: array<int, int>}
     */
    private function timeseries(Carbon $from, Carbon $to, string $granularity): array
    {
        $bucketExpression = match ($granularity) {
            'week' => "DATE_FORMAT(viewed_at, '%x-W%v')",
            'month' => "DATE_FORMAT(viewed_at, '%Y-%m')",
            default => "DATE_FORMAT(viewed_at, '%Y-%m-%d')",
        };

        $baseQuery = PageView::query()->whereBetween('viewed_at', [$from, $to]);

        $viewsByBucket = (clone $baseQuery)
            ->selectRaw("{$bucketExpression} as bucket, COUNT(*) as total")
            ->groupByRaw($bucketExpression)
            ->pluck('total', 'bucket');

        $visitorsByBucket = (clone $baseQuery)
            ->whereNotNull('session_hash')
            ->selectRaw("{$bucketExpression} as bucket, COUNT(DISTINCT session_hash) as total")
            ->groupByRaw($bucketExpression)
            ->pluck('total', 'bucket');

        $labels = [];
        $views = [];
        $visitors = [];

        $cursor = match ($granularity) {
            'week' => $from->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $from->copy()->startOfMonth(),
            default => $from->copy(),
        };

        while ($cursor->lte($to)) {
            $key = match ($granularity) {
                'week' => $cursor->format('o-\WW'),
                'month' => $cursor->format('Y-m'),
                default => $cursor->format('Y-m-d'),
            };

            $labels[] = match ($granularity) {
                'week' => 'Sem ' . $cursor->format('W'),
                'month' => ucfirst($cursor->copy()->locale('es')->translatedFormat('M Y')),
                default => $cursor->copy()->locale('es')->translatedFormat('j M'),
            };

            $views[] = (int) ($viewsByBucket[$key] ?? 0);
            $visitors[] = (int) ($visitorsByBucket[$key] ?? 0);

            match ($granularity) {
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonthNoOverflow()->startOfMonth(),
                default => $cursor->addDay(),
            };
        }

        return [
            'labels' => $labels,
            'views' => $views,
            'visitors' => $visitors,
        ];
    }

    /**
     * @return array<int, array{path: string, views: int, visitors: int, percentage: float}>
     */
    private function topPages($baseQuery, int $totalViews): array
    {
        return (clone $baseQuery)
            ->selectRaw('path, COUNT(*) as views, COUNT(DISTINCT session_hash) as visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
                'percentage' => $totalViews > 0 ? round(((int) $row->views / $totalViews) * 100, 1) : 0,
            ])
            ->all();
    }

    /**
     * @return array<int, array{device: string, views: int, percentage: float}>
     */
    private function devices($baseQuery, int $totalViews): array
    {
        return (clone $baseQuery)
            ->selectRaw("COALESCE(device, 'desconocido') as device, COUNT(*) as views")
            ->groupBy('device')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => [
                'device' => $row->device,
                'views' => (int) $row->views,
                'percentage' => $totalViews > 0 ? round(((int) $row->views / $totalViews) * 100, 1) : 0,
            ])
            ->all();
    }

    /**
     * @return array<int, array{source: string, views: int, percentage: float}>
     */
    private function referrers($baseQuery, int $totalViews, Request $request): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: $request->getHost();

        $rows = (clone $baseQuery)
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->selectRaw('referrer, COUNT(*) as views')
            ->groupBy('referrer')
            ->orderByDesc('views')
            ->limit(100)
            ->get();

        $external = [];

        foreach ($rows as $row) {
            $host = parse_url((string) $row->referrer, PHP_URL_HOST);

            if (! $host || $host === $appHost || $host === $request->getHost()) {
                continue;
            }

            $external[$host] = ($external[$host] ?? 0) + (int) $row->views;
        }

        arsort($external);

        $sources = [];
        $externalTotal = 0;

        foreach (array_slice($external, 0, 6, true) as $host => $sourceViews) {
            $externalTotal += $sourceViews;
            $sources[] = [
                'source' => $host,
                'views' => $sourceViews,
                'percentage' => $totalViews > 0 ? round(($sourceViews / $totalViews) * 100, 1) : 0,
            ];
        }

        $direct = max($totalViews - $externalTotal, 0);

        if ($direct > 0) {
            array_unshift($sources, [
                'source' => 'Directo',
                'views' => $direct,
                'percentage' => $totalViews > 0 ? round(($direct / $totalViews) * 100, 1) : 0,
            ]);
        }

        return $sources;
    }

    private function percentageChange(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
