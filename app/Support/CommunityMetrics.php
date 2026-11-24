<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\HealthSnapshot;
use App\Models\Rating;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;

/** مراحل حياة العضو، مؤشر صحة المجتمع، وتحليل الأفواج الشهرية */
class CommunityMetrics
{
    public const STAGES = [
        'new' => 'جديد',
        'active' => 'نشط',
        'returning' => 'عائد',
        'at_risk' => 'مهدّد بالمغادرة',
        'inactive' => 'غير نشط',
    ];

    /** @return array<int, array{created:\Illuminate\Support\Carbon,seen:\Illuminate\Support\Carbon,dates:Collection}> */
    private static function memberData(): array
    {
        // تواريخ الحضور الفعلي لكل عضو (مرتبة تصاعديًا)
        $dates = EventAttendance::where('seconds', '>=', EventAttendance::THRESHOLD)
            ->join('events', 'events.id', '=', 'event_attendances.event_id')
            ->orderBy('events.starts_at')
            ->get(['event_attendances.user_id', 'events.starts_at'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => \Illuminate\Support\Carbon::parse($r->starts_at))->values());

        $out = [];
        foreach (User::where('is_admin', false)->get(['id', 'created_at', 'last_seen_at']) as $u) {
            $out[$u->id] = [
                'created' => $u->created_at,
                'seen' => $u->last_seen_at ?? $u->created_at,
                'dates' => $dates[$u->id] ?? collect(),
            ];
        }

        return $out;
    }

    /** مرحلة حياة عضو واحد */
    public static function stageOf(array $m): string
    {
        $now = now();

        if ($m['created']->gte($now->copy()->subDays(14))) {
            return 'new';
        }

        // عائد: حضر خلال آخر 30 يومًا بعد انقطاع 60 يومًا أو أكثر عن حضوره السابق
        $d = $m['dates'];
        if ($d->count() >= 2) {
            $last = $d[$d->count() - 1];
            $prev = $d[$d->count() - 2];
            if ($last->gte($now->copy()->subDays(30)) && $prev->diffInDays($last) >= 60) {
                return 'returning';
            }
        }

        if ($m['seen']->gte($now->copy()->subDays(30))) {
            return 'active';
        }

        return $m['seen']->gte($now->copy()->subDays(90)) ? 'at_risk' : 'inactive';
    }

    /** @return array<string,int> عدد الأعضاء في كل مرحلة */
    public static function lifecycle(): array
    {
        $counts = array_fill_keys(array_keys(self::STAGES), 0);
        foreach (self::memberData() as $m) {
            $counts[self::stageOf($m)]++;
        }

        return $counts;
    }

    /** مؤشر صحة المجتمع من 100 بمكونات موزونة وقابلة للتفسير */
    public static function health(): array
    {
        $threshold = EventAttendance::THRESHOLD;
        $members = User::where('is_admin', false)->count();
        $window = now()->subDays(90);

        $parts = [];

        // 1) النشاط: نسبة الأعضاء الذين ظهروا آخر 30 يومًا
        if ($members > 0) {
            $active = User::where('is_admin', false)->where('last_seen_at', '>=', now()->subDays(30))->count();
            $parts[] = ['key' => 'activity', 'label' => 'النشاط', 'weight' => 30, 'ratio' => $active / $members, 'text' => $active.' من '.$members.' نشطون آخر 30 يومًا'];
        }

        // 2) الحضور: الحضور الفعلي من المقبولين في أحداث انتهت خلال 90 يومًا
        $events = Event::where('status', Event::PUBLISHED)->where('starts_at', '>=', $window)->where('starts_at', '<', now())
            ->withCount([
                'acceptedRequests as accepted_count',
                'attendances as attended_count' => fn ($q) => $q->where('seconds', '>=', $threshold)->whereColumn('event_attendances.user_id', '!=', 'events.user_id'),
            ])->get()->filter(fn ($e) => $e->hasEnded() && $e->accepted_count > 0);
        $accepted = $events->sum('accepted_count');
        if ($accepted > 0) {
            $attended = $events->sum(fn ($e) => min($e->attended_count, $e->accepted_count));
            $parts[] = ['key' => 'attendance', 'label' => 'الحضور', 'weight' => 25, 'ratio' => $attended / $accepted, 'text' => $attended.' حضروا من '.$accepted.' مقبولًا'];
        }

        // 3) الرضا: متوسط التقييم آخر 90 يومًا
        $avg = Rating::where('created_at', '>=', $window)->avg('stars');
        if ($avg !== null) {
            $parts[] = ['key' => 'rating', 'label' => 'الرضا', 'weight' => 20, 'ratio' => max(0, min(1, ((float) $avg - 1) / 4)), 'text' => 'متوسط التقييم '.round((float) $avg, 1).' من 5'];
        }

        // 4) الاحتفاظ: من حضر أكثر من لقاء
        $attenders = EventAttendance::where('seconds', '>=', $threshold)->distinct('user_id')->count('user_id');
        if ($attenders > 0) {
            $returning = EventAttendance::where('seconds', '>=', $threshold)->select('user_id')->groupBy('user_id')->havingRaw('count(*) >= 2')->get()->count();
            $parts[] = ['key' => 'retention', 'label' => 'الاحتفاظ', 'weight' => 15, 'ratio' => $returning / $attenders, 'text' => $returning.' من '.$attenders.' حضروا أكثر من لقاء'];
        }

        // 5) السلامة: البلاغات آخر 30 يومًا نسبةً إلى الأعضاء (5٪ فأكثر = صفر)
        if ($members > 0) {
            $reports = Report::where('created_at', '>=', now()->subDays(30))->count();
            $parts[] = ['key' => 'safety', 'label' => 'السلامة', 'weight' => 10, 'ratio' => 1 - min(1, ($reports / $members) / 0.05), 'text' => $reports.' بلاغ آخر 30 يومًا'];
        }

        $max = array_sum(array_column($parts, 'weight'));
        $got = array_sum(array_map(fn ($p) => $p['weight'] * $p['ratio'], $parts));
        $score = $max > 0 ? (int) round($got / $max * 100) : null;

        foreach ($parts as &$p) {
            $p['points'] = round($p['weight'] * $p['ratio'], 1);
            $p['percent'] = (int) round($p['ratio'] * 100);
        }
        unset($p);

        return [
            'score' => $score,
            'label' => $score === null ? '—' : ($score >= 80 ? 'ممتاز' : ($score >= 60 ? 'جيد' : ($score >= 40 ? 'متوسط' : 'يحتاج اهتمامًا'))),
            'parts' => $parts,
            'missing' => count($parts) < 5,
        ];
    }

    /** يحفظ لقطة اليوم (مرة واحدة) ويعيد التغيّر عن أقدم لقطة خلال آخر 7–30 يومًا */
    public static function snapshot(?int $score, array $parts): ?int
    {
        if ($score === null) {
            return null;
        }

        HealthSnapshot::firstOrCreate(['day' => now()->toDateString()], ['score' => $score, 'parts' => $parts]);

        $old = HealthSnapshot::where('day', '<=', now()->subDays(7)->toDateString())->where('day', '>=', now()->subDays(30)->toDateString())
            ->orderBy('day')->first();

        return $old ? $score - $old->score : null;
    }

    /** الأفواج: أعضاء كل شهر تسجيل ومدى التزامهم */
    public static function cohorts(int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $members = User::where('is_admin', false)->where('created_at', '>=', $start)->get(['id', 'created_at', 'last_seen_at']);

        $counts = EventAttendance::where('seconds', '>=', EventAttendance::THRESHOLD)
            ->whereIn('user_id', $members->pluck('id'))->selectRaw('user_id, count(*) as c')->groupBy('user_id')->pluck('c', 'user_id');

        $rows = [];
        for ($i = 0; $i < $months; $i++) {
            $from = $start->copy()->addMonths($i);
            $to = $from->copy()->addMonth();
            $group = $members->filter(fn ($u) => $u->created_at->gte($from) && $u->created_at->lt($to));
            $n = $group->count();
            $once = $group->filter(fn ($u) => ($counts[$u->id] ?? 0) >= 1)->count();
            $twice = $group->filter(fn ($u) => ($counts[$u->id] ?? 0) >= 2)->count();
            $act = $group->filter(fn ($u) => ($u->last_seen_at ?? $u->created_at)->gte(now()->subDays(30)))->count();
            $pct = fn ($x) => $n > 0 ? (int) round($x / $n * 100) : null;
            $rows[] = ['month' => $from->translatedFormat('F Y'), 'size' => $n, 'attended' => $pct($once), 'returned' => $pct($twice), 'active' => $pct($act)];
        }

        return array_reverse($rows);
    }
}
