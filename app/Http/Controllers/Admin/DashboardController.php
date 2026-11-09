<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\FormTemplate;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $counted = Evaluation::counted();

        $stats = [
            'total'   => (clone $counted)->count(),
            'week'    => (clone $counted)->where('created_at', '>=', now()->subDays(7))->count(),
            'avg'     => (clone $counted)->avg('average_score'),
            'rooms'   => Room::where('is_active', true)->count(),
            'flagged' => Evaluation::where('status', 'flagged')->count(),
        ];

        $byRoom = Room::query()
            ->withCount(['evaluations as responses' => fn ($q) => $q->counted()])
            ->withAvg(['evaluations as avg' => fn ($q) => $q->counted()], 'average_score')
            ->orderBy('name')->get();

        // Responses per day, last 14 days (gaps filled with zero).
        $raw = Evaluation::counted()
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->select(DB::raw('date(created_at) as d'), DB::raw('count(*) as c'))
            ->groupBy('d')->pluck('c', 'd');
        $trend = collect(range(13, 0))->map(function ($i) use ($raw) {
            $day = now()->subDays($i);

            return ['label' => $day->format('M j'), 'short' => $day->format('j'), 'count' => (int) ($raw[$day->toDateString()] ?? 0)];
        });

        $recent = Evaluation::with('room')->latest()->limit(8)->get();

        return view('admin.dashboard', [
            'stats'    => $stats,
            'byRoom'   => $byRoom,
            'trend'    => $trend,
            'maxTrend' => max(1, $trend->max('count')),
            'recent'   => $recent,
            'template' => FormTemplate::active(),
        ]);
    }

    /** Wall-display / printable monitoring sheet. */
    public function monitor()
    {
        $rooms = Room::query()
            ->withCount([
                'evaluations as today' => fn ($q) => $q->counted()->where('created_at', '>=', now()->startOfDay()),
                'evaluations as week'  => fn ($q) => $q->counted()->where('created_at', '>=', now()->subDays(7)),
            ])
            ->withAvg(['evaluations as week_avg' => fn ($q) => $q->counted()->where('created_at', '>=', now()->subDays(7))], 'average_score')
            ->withMax('evaluations as last_at', 'created_at')
            ->orderBy('name')->get();

        return view('admin.monitor', [
            'rooms'  => $rooms,
            'recent' => Evaluation::with('room')->latest()->limit(25)->get(),
        ]);
    }
}
