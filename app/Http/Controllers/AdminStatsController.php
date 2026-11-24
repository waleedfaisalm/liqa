<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\EventRoomEntry;
use App\Models\Rating;
use App\Models\Report;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** لوحة إحصاءات المالك: النمو، الأحداث، الحضور، الغرف المباشرة */
class AdminStatsController extends Controller
{
    public function index(Request $request): View
    {
        $range = (int) $request->query('range') === 90 ? 90 : 30;
        $from = now()->subDays($range - 1)->startOfDay();
        $prevFrom = $from->copy()->subDays($range);

        $days = collect(range($range - 1, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $series = fn (Collection $dates) => $this->perDay($days, $dates);

        // ---- مؤشرات الفترة مقابل الفترة السابقة ----
        $count = fn ($q, string $col = 'created_at') => [
            (clone $q)->where($col, '>=', $from)->count(),
            (clone $q)->where($col, '>=', $prevFrom)->where($col, '<', $from)->count(),
        ];

        [$newUsers, $prevUsers] = $count(User::query());
        [$newEvents, $prevEvents] = $count(Event::query());
        [$joined, $prevJoined] = $count(EventRequest::where('status', EventRequest::ACCEPTED), 'updated_at');
        [$ratings, $prevRatings] = $count(Rating::query());

        $kpis = [
            ['label' => 'أعضاء جدد', 'value' => $newUsers, 'prev' => $prevUsers, 'total' => User::count(), 'totalLabel' => 'الإجمالي'],
            ['label' => 'أحداث جديدة', 'value' => $newEvents, 'prev' => $prevEvents, 'total' => Event::count(), 'totalLabel' => 'الإجمالي'],
            ['label' => 'طلبات مقبولة', 'value' => $joined, 'prev' => $prevJoined, 'total' => EventRequest::where('status', EventRequest::ACCEPTED)->count(), 'totalLabel' => 'الإجمالي'],
            ['label' => 'تقييمات', 'value' => $ratings, 'prev' => $prevRatings, 'total' => Rating::count(), 'totalLabel' => 'الإجمالي'],
        ];

        // ---- الرسوم اليومية ----
        $daily = [
            'labels' => $days->map(fn ($d) => $d->format('j/n'))->all(),
            'growth' => [
                ['name' => 'أعضاء جدد', 'color' => 'var(--series-1)', 'data' => $series(User::where('created_at', '>=', $from)->pluck('created_at'))],
                ['name' => 'أحداث جديدة', 'color' => 'var(--series-2)', 'data' => $series(Event::where('created_at', '>=', $from)->pluck('created_at'))],
            ],
            'requests' => [
                ['name' => 'طلبات انضمام', 'color' => 'var(--series-3)', 'data' => $series(EventRequest::where('created_at', '>=', $from)->pluck('created_at'))],
                ['name' => 'طلبات مقبولة', 'color' => 'var(--series-1)', 'data' => $series(EventRequest::where('status', EventRequest::ACCEPTED)->where('updated_at', '>=', $from)->pluck('updated_at'))],
            ],
        ];

        // ---- قمع النشاط ----
        $registered = User::count();
        $onboarded = User::whereNotNull('onboarded_at')->count();
        $hosts = Event::where('status', Event::PUBLISHED)->distinct('user_id')->count('user_id');
        $attendees = EventRequest::where('status', EventRequest::ACCEPTED)->distinct('user_id')->count('user_id');
        $returning = EventRequest::where('status', EventRequest::ACCEPTED)->select('user_id')->groupBy('user_id')->havingRaw('count(*) >= 2')->get()->count();
        $rated = Rating::distinct('rater_id')->count('rater_id');

        $funnel = [
            ['label' => 'سجّلوا في المنصة', 'value' => $registered],
            ['label' => 'أكملوا الإعداد الأولي', 'value' => $onboarded],
            ['label' => 'نشروا حدثًا', 'value' => $hosts],
            ['label' => 'طلبوا الانضمام لحدث', 'value' => EventRequest::distinct('user_id')->count('user_id')],
            ['label' => 'قُبلوا في حدث', 'value' => $attendees],
            ['label' => 'حضروا فعليًا', 'value' => \App\Models\EventAttendance::where('seconds', '>=', \App\Models\EventAttendance::THRESHOLD)->distinct('user_id')->count('user_id')],
            ['label' => 'حضروا أكثر من لقاء', 'value' => $returning],
            ['label' => 'قيّموا أعضاء', 'value' => $rated],
        ];

        // ---- الأحداث ----
        $published = Event::where('status', Event::PUBLISHED);
        $byType = (clone $published)->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type');
        $types = collect(Catalog::eventTypes(false))->map(fn ($l, $k) => ['label' => $l, 'value' => (int) ($byType[$k] ?? 0)])->sortByDesc('value')->values()->all();

        $weekdayNames = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        $weekdays = array_fill(0, 7, 0);
        (clone $published)->pluck('starts_at')->each(function ($d) use (&$weekdays) {
            $weekdays[Carbon::parse($d)->dayOfWeek]++;
        });
        $weekdayRows = collect($weekdayNames)->map(fn ($n, $i) => ['label' => $n, 'value' => $weekdays[$i]])->all();

        $modes = [
            ['label' => 'حضوري', 'value' => (clone $published)->where('mode', 'in_person')->count()],
            ['label' => 'عن بُعد', 'value' => (clone $published)->where('mode', 'online')->count()],
        ];

        $cities = (clone $published)->whereNotNull('city')->selectRaw('city, count(*) as c')->groupBy('city')->orderByDesc('c')->limit(8)->pluck('c', 'city')
            ->map(fn ($c, $k) => ['label' => $k, 'value' => (int) $c])->values()->all();

        $pastQuery = (clone $published)->where('starts_at', '<', now());
        $pastEvents = (clone $pastQuery)->withCount(['requests as accepted_count' => fn ($q) => $q->where('status', EventRequest::ACCEPTED)])->get(['id', 'capacity']);
        $fill = $pastEvents->filter(fn ($e) => $e->capacity > 0)->map(fn ($e) => min(1, $e->accepted_count / $e->capacity));
        $avgFill = $fill->isEmpty() ? null : round($fill->avg() * 100);
        $avgAttendees = $pastEvents->isEmpty() ? null : round($pastEvents->avg('accepted_count'), 1);
        $cancelled = Event::where('status', Event::CANCELLED)->count();
        $cancelRate = Event::count() > 0 ? round($cancelled / Event::count() * 100, 1) : 0;
        $emptyEvents = $pastEvents->where('accepted_count', 0)->count();

        // ---- الطلبات ----
        $byStatus = EventRequest::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $statusRows = collect([
            EventRequest::PENDING => 'قيد المراجعة',
            EventRequest::ACCEPTED => 'مقبولة',
            EventRequest::REJECTED => 'مرفوضة',
            EventRequest::WAITLISTED => 'قائمة الانتظار',
        ])->map(fn ($l, $k) => ['label' => $l, 'value' => (int) ($byStatus[$k] ?? 0)])->values()->all();

        // ---- الغرف المباشرة ----
        $room = [
            'events' => EventRoomEntry::distinct('event_id')->count('event_id'),
            'admitted' => EventRoomEntry::where('status', EventRoomEntry::ADMITTED)->count(),
            'viaLink' => EventRoomEntry::where('via_link', true)->where('status', EventRoomEntry::ADMITTED)->count(),
            'removed' => EventRoomEntry::where('status', EventRoomEntry::REMOVED)->count(),
        ];

        // ---- التخصصات وأفضل المنظّمين ----
        $bySpec = User::whereNotNull('specialty')->selectRaw('specialty, count(*) as c')->groupBy('specialty')->orderByDesc('c')->pluck('c', 'specialty')
            ->map(fn ($c, $k) => ['label' => $k, 'value' => (int) $c])->values()->all();

        $topHosts = User::withCount(['hostedEvents as hosted_count' => fn ($q) => $q->where('status', Event::PUBLISHED)])
            ->withAvg('ratingsReceived as avg_rating', 'stars')
            ->whereHas('hostedEvents', fn ($q) => $q->where('status', Event::PUBLISHED))
            ->orderByDesc('hosted_count')->limit(5)->get();

        $active = [
            ['label' => 'آخر ٢٤ ساعة', 'value' => User::where('last_seen_at', '>=', now()->subDay())->count()],
            ['label' => 'آخر ٧ أيام', 'value' => User::where('last_seen_at', '>=', now()->subDays(7))->count()],
            ['label' => 'آخر ٣٠ يومًا', 'value' => User::where('last_seen_at', '>=', now()->subDays(30))->count()],
        ];

        $health = \App\Support\CommunityMetrics::health();
        $healthDelta = \App\Support\CommunityMetrics::snapshot($health['score'], $health['parts']);

        return view('admin.stats', [
            'health' => $health,
            'healthDelta' => $healthDelta,
            'lifecycle' => \App\Support\CommunityMetrics::lifecycle(),
            'cohorts' => \App\Support\CommunityMetrics::cohorts(),
            'report' => \App\Support\AnalyticsReport::build($range),
            'range' => $range,
            'kpis' => $kpis,
            'daily' => $daily,
            'funnel' => $funnel,
            'types' => $types,
            'weekdays' => $weekdayRows,
            'modes' => $modes,
            'cities' => $cities,
            'statusRows' => $statusRows,
            'room' => $room,
            'bySpec' => $bySpec,
            'topHosts' => $topHosts,
            'active' => $active,
            'quality' => [
                'avgFill' => $avgFill,
                'avgAttendees' => $avgAttendees,
                'cancelRate' => $cancelRate,
                'emptyEvents' => $emptyEvents,
                'pastEvents' => $pastEvents->count(),
                'avgRating' => Rating::avg('stars') ? round((float) Rating::avg('stars'), 2) : null,
                'openReports' => Report::where('status', 'open')->count(),
            ],
        ]);
    }

    /** تقرير قابل للطباعة / الحفظ PDF */
    public function report(Request $request): View
    {
        $range = in_array((int) $request->query('range'), [7, 30, 90], true) ? (int) $request->query('range') : 30;

        return view('admin.report', ['r' => \App\Support\AnalyticsReport::build($range), 'range' => $range]);
    }

    /** @return list<int> عدد السجلات في كل يوم من الأيام المطلوبة */
    private function perDay(Collection $days, Collection $dates): array
    {
        $counts = $dates->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))->countBy();

        return $days->map(fn ($d) => (int) ($counts[$d->format('Y-m-d')] ?? 0))->all();
    }
}
