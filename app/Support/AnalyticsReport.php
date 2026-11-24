<?php

namespace App\Support;

use App\Models\ContactRequest;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventRequest;
use App\Models\Rating;
use App\Models\User;

/** تقرير تحليلي موحّد: يُستخدم في لوحة الإحصاءات والتقرير القابل للطباعة والبريد الأسبوعي */
class AnalyticsReport
{
    public static function build(int $days): array
    {
        $from = now()->subDays($days)->startOfDay();
        $prevFrom = $from->copy()->subDays($days);
        $threshold = EventAttendance::THRESHOLD;

        $pair = fn ($query, string $col = 'created_at') => [
            (clone $query)->where($col, '>=', $from)->count(),
            (clone $query)->where($col, '>=', $prevFrom)->where($col, '<', $from)->count(),
        ];

        $rows = [
            ['أعضاء جدد', ...$pair(User::where('is_admin', false))],
            ['أحداث منشورة', ...$pair(Event::where('status', Event::PUBLISHED))],
            ['طلبات مقبولة', ...$pair(EventRequest::where('status', EventRequest::ACCEPTED), 'updated_at')],
            ['حضور فعلي مسجّل', ...$pair(EventAttendance::where('seconds', '>=', $threshold))],
            ['شهادات صادرة', ...$pair(EventAttendance::whereNotNull('certificate_code'), 'updated_at')],
            ['طلبات تواصل', ...$pair(ContactRequest::query())],
            ['تواصل مقبول', ...$pair(ContactRequest::where('status', ContactRequest::ACCEPTED), 'updated_at')],
            ['تقييمات', ...$pair(Rating::query())],
        ];

        $kpis = array_map(fn ($r) => [
            'label' => $r[0], 'value' => $r[1], 'prev' => $r[2],
            'delta' => $r[2] > 0 ? (int) round(($r[1] - $r[2]) / $r[2] * 100) : null,
        ], $rows);

        // ---- أحداث انتهت خلال الفترة: الحضور الفعلي مقابل المقبولين ----
        $ended = Event::where('status', Event::PUBLISHED)
            ->where('starts_at', '>=', $from)->where('starts_at', '<', now())
            ->withCount([
                'acceptedRequests as accepted_count',
                'attendances as attended_count' => fn ($q) => $q->where('seconds', '>=', $threshold)->whereColumn('event_attendances.user_id', '!=', 'events.user_id'),
            ])
            ->withAvg('ratings as avg_rating', 'stars')
            ->get()
            ->filter(fn ($e) => $e->hasEnded())
            ->values();

        $withAccepted = $ended->where('accepted_count', '>', 0);
        $acceptedSum = $withAccepted->sum('accepted_count');
        $attendedSum = $withAccepted->sum(fn ($e) => min($e->attended_count, $e->accepted_count));
        $attendanceRate = $acceptedSum > 0 ? (int) round($attendedSum / $acceptedSum * 100) : null;

        $hostOf = $ended->pluck('user_id', 'id');
        $avgSeconds = EventAttendance::whereIn('event_id', $hostOf->keys())->get(['event_id', 'user_id', 'seconds'])
            ->filter(fn ($a) => $a->user_id !== $hostOf[$a->event_id])->avg('seconds');

        // ---- الاحتفاظ ----
        $attendersAll = EventAttendance::where('seconds', '>=', $threshold)->select('user_id')->groupBy('user_id')->havingRaw('count(*) >= 1')->get()->count();
        $returning = EventAttendance::where('seconds', '>=', $threshold)->select('user_id')->groupBy('user_id')->havingRaw('count(*) >= 2')->get()->count();
        $members = User::where('is_admin', false)->count();
        $active30 = User::where('is_admin', false)->where('last_seen_at', '>=', now()->subDays(30))->count();

        $retention = [
            'returningRate' => $attendersAll > 0 ? (int) round($returning / $attendersAll * 100) : null,
            'returning' => $returning,
            'attenders' => $attendersAll,
            'active30Rate' => $members > 0 ? (int) round($active30 / $members * 100) : null,
            'active30' => $active30,
            'members' => $members,
        ];

        // ---- أكثر المجالات والمدن نشاطًا في الفترة ----
        $inPeriod = Event::where('status', Event::PUBLISHED)->where('starts_at', '>=', $from);
        $typeLabels = Catalog::eventTypes(false);
        $types = (clone $inPeriod)->selectRaw('type, count(*) as c')->groupBy('type')->orderByDesc('c')->limit(5)->pluck('c', 'type')
            ->map(fn ($c, $k) => ['label' => $typeLabels[$k] ?? $k, 'value' => (int) $c])->values()->all();
        $cities = (clone $inPeriod)->whereNotNull('city')->selectRaw('city, count(*) as c')->groupBy('city')->orderByDesc('c')->limit(5)->pluck('c', 'city')
            ->map(fn ($c, $k) => ['label' => $k, 'value' => (int) $c])->values()->all();

        $topEvents = $ended->sortByDesc('attended_count')->take(5)->map(fn ($e) => [
            'title' => $e->title,
            'date' => $e->starts_at->format('Y-m-d'),
            'accepted' => $e->accepted_count,
            'attended' => $e->attended_count,
            'rating' => $e->avg_rating ? round((float) $e->avg_rating, 1) : null,
        ])->values()->all();

        return [
            'days' => $days,
            'from' => $from,
            'to' => now(),
            'kpis' => $kpis,
            'endedEvents' => $ended->count(),
            'attendanceRate' => $attendanceRate,
            'avgMinutes' => $avgSeconds ? (int) round($avgSeconds / 60) : null,
            'retention' => $retention,
            'health' => CommunityMetrics::health(),
            'lifecycle' => CommunityMetrics::lifecycle(),
            'types' => $types,
            'cities' => $cities,
            'topEvents' => $topEvents,
        ];
    }
}
