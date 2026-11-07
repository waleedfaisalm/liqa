<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Event;
use App\Models\EventComment;
use App\Models\EventRequest;
use App\Models\Rating;
use App\Models\Report;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'users_week' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
            'suspended' => User::suspended()->count(),
            'events_total' => Event::count(),
            'events_upcoming' => Event::upcoming()->count(),
            'events_past' => Event::where('status', Event::PUBLISHED)->where('starts_at', '<', now())->count(),
            'events_cancelled' => Event::where('status', Event::CANCELLED)->count(),
            'requests_pending' => EventRequest::where('status', EventRequest::PENDING)->count(),
            'requests_accepted' => EventRequest::where('status', EventRequest::ACCEPTED)->count(),
            'ratings' => Rating::count(),
            'comments' => EventComment::count(),
            'online' => User::online()->count(),
            'reports_open' => Report::where('status', 'open')->count(),
        ];

        $days = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $daily = function ($model) use ($days) {
            $rows = $model::where('created_at', '>=', $days->first())
                ->get(['created_at'])->groupBy(fn ($r) => $r->created_at->format('Y-m-d'))->map->count();

            return $days->map(fn ($d) => (int) ($rows[$d->format('Y-m-d')] ?? 0))->all();
        };

        $byType = Event::where('status', Event::PUBLISHED)->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type');
        $types = collect(\App\Support\Catalog::eventTypes(false))->map(fn ($label, $key) => ['label' => $label, 'value' => (int) ($byType[$key] ?? 0)])->sortByDesc('value')->values()->all();

        $online = Event::where('status', Event::PUBLISHED)->where('mode', 'online')->count();
        $byCountry = Event::where('status', Event::PUBLISHED)->where('mode', '!=', 'online')->whereNotNull('country')
            ->selectRaw('country, count(*) as c')->groupBy('country')->orderByDesc('c')->limit(5)->pluck('c', 'country');
        $places = collect($byCountry)->map(fn ($c, $k) => ['label' => $k, 'value' => (int) $c])->values()->all();
        if ($online > 0) {
            $places[] = ['label' => 'عن بُعد', 'value' => $online];
            usort($places, fn ($a, $b) => $b['value'] <=> $a['value']);
        }

        return view('admin.dashboard', [
            'chart' => [
                'labels' => $days->map(fn ($d) => $d->format('j/n'))->all(),
                'signups' => $daily(User::class),
                'events' => $daily(Event::class),
            ],
            'types' => $types,
            'places' => $places,
            'recentLogs' => AdminLog::orderByDesc('id')->limit(6)->get(),
            'stats' => $stats,
            'onlineUsers' => User::online()->orderByDesc('last_seen_at')->limit(18)->get(),
            'recentUsers' => User::latest()->limit(6)->get(),
            'openReports' => Report::with('reporter')->where('status', 'open')->latest()->limit(5)->get(),
            'soonEvents' => Event::upcoming()->with('host')->withCount('acceptedRequests')->orderBy('starts_at')->limit(5)->get(),
        ]);
    }
}
