<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Reliability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/** الطلب والحضور: فجوة العرض والطلب بالمدن، ترشيح منظّمين، أفضل وقت، والتزام الأعضاء بالحضور */
class AdminDemandController extends Controller
{
    private const DAYS = [6 => 'السبت', 0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة'];

    private const BUCKETS = ['morning' => 'صباحًا', 'afternoon' => 'ظهرًا وعصرًا', 'evening' => 'مساءً', 'night' => 'ليلًا'];

    private static function key(?string $city): string
    {
        return mb_strtolower(trim((string) $city));
    }

    private static function bucket(int $hour): string
    {
        return match (true) {
            $hour >= 6 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 17 => 'afternoon',
            $hour >= 17 && $hour < 21 => 'evening',
            default => 'night',
        };
    }

    public function index(): View
    {
        Reliability::flush();

        // ---------- الطلب غير الملبّى ----------
        $members = User::where('is_admin', false)->whereNotNull('city')->where('city', '!=', '')->get(['id', 'city']);
        $noCity = User::where('is_admin', false)->where(fn ($q) => $q->whereNull('city')->orWhere('city', ''))->count();

        $events90 = Event::where('status', Event::PUBLISHED)->whereNotNull('city')->where('starts_at', '>=', now()->subDays(90))->get(['id', 'city']);
        $eventsByCity = $events90->groupBy(fn ($e) => self::key($e->city))->map->count();

        $cities = $members->groupBy(fn ($u) => self::key($u->city))->map(function ($g, $k) use ($eventsByCity) {
            $events = $eventsByCity[$k] ?? 0;

            return [
                'key' => $k,
                'name' => $g->pluck('city')->map(fn ($c) => trim($c))->countBy()->sortDesc()->keys()->first(),
                'members' => $g->count(),
                'events' => $events,
                'ratio' => $g->count() / ($events + 1),
            ];
        })->sortByDesc('ratio')->values();

        $gaps = $cities->filter(fn ($c) => $c['members'] >= 3 && ($c['events'] === 0 || $c['ratio'] >= 8))->take(8)->values();

        // ---------- مرشّحو التنظيم ----------
        $attended = EventAttendance::where('seconds', '>=', EventAttendance::THRESHOLD)->get(['user_id'])->countBy('user_id');
        $hosted = Event::where('status', Event::PUBLISHED)->get(['user_id'])->countBy('user_id');
        $invited = UserNotification::where('type', 'organizer_invite')->where('created_at', '>=', now()->subDays(30))->pluck('user_id')->flip();

        $nominees = [];
        foreach ($gaps->take(4) as $gap) {
            $ids = $members->filter(fn ($u) => self::key($u->city) === $gap['key'])->pluck('id');
            $nominees[$gap['key']] = User::whereIn('id', $ids)->whereNull('suspended_at')->get()
                ->filter(fn ($u) => ($attended[$u->id] ?? 0) >= 2 && ($hosted[$u->id] ?? 0) === 0 && ($u->risk_score === null || $u->risk_score < 30 || $u->risk_cleared_at))
                ->sortByDesc(fn ($u) => ($attended[$u->id] ?? 0) + ($u->isVerified() ? 5 : 0))
                ->take(5)->values();
        }

        // ---------- أفضل وقت ----------
        $past = Event::where('status', Event::PUBLISHED)->where('capacity', '>', 0)->where('starts_at', '<', now())->withCount('acceptedRequests')->get()
            ->filter(fn ($e) => $e->hasEnded());

        $cells = [];
        foreach ($past as $e) {
            $k = $e->starts_at->dayOfWeek.'|'.self::bucket((int) $e->starts_at->format('G'));
            $cells[$k][] = min(1, $e->accepted_requests_count / $e->capacity);
        }
        $slots = collect($cells)->map(function ($v, $k) {
            [$d, $b] = explode('|', $k);

            return ['day' => self::DAYS[(int) $d], 'bucket' => self::BUCKETS[$b], 'fill' => round(array_sum($v) / count($v) * 100), 'n' => count($v), 'd' => (int) $d, 'b' => $b];
        })->sortByDesc(fn ($s) => $s['fill'] * 1000 + $s['n'])->values();

        // ---------- الغياب ----------
        $hist = Reliability::history();
        $absentUsers = User::whereIn('id', collect($hist)->filter(fn ($h) => $h['missed'] > 0)->keys())->get()
            ->map(fn ($u) => ['user' => $u] + $hist[$u->id])
            ->sortByDesc(fn ($r) => $r['recent_missed'] * 100 + $r['missed'])->take(20)->values();

        $restricted = User::where('join_restricted_until', '>', now())->orderBy('join_restricted_until')->get();

        return view('admin.demand', compact('gaps', 'cities', 'noCity', 'nominees', 'invited', 'slots', 'absentUsers', 'restricted', 'hist') + ['days' => self::DAYS, 'buckets' => self::BUCKETS, 'pastCount' => $past->count()]);
    }

    public function nominate(Request $request, User $user): RedirectResponse
    {
        if (UserNotification::where('user_id', $user->id)->where('type', 'organizer_invite')->where('created_at', '>=', now()->subDays(30))->exists()) {
            return back()->with('error', 'سبق ترشيح هذا العضو خلال آخر 30 يومًا.');
        }
        if (! $user->notifications_enabled) {
            return back()->with('error', 'هذا العضو أوقف الإشعارات، تواصل معه بوسيلة أخرى.');
        }

        UserNotification::send($user, 'organizer_invite', 'رشّحتك إدارة لقاء لتنظيم أول لقاء في مدينتك'.($user->city ? ' «'.$user->city.'»' : '').'، فأعضاء كثيرون هناك ينتظرون لقاءً قريبًا. ابدأ بحدث صغير!', route('events.create'));
        AdminLog::record($request->user(), 'user.nominated', 'user', $user->id, $user->name, $user->city);

        return back()->with('success', 'أُرسل الترشيح إلى '.$user->name.'.');
    }

    public function restrict(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);

        $data = $request->validate([
            'days' => ['required', 'integer', 'in:3,7,14,30'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        $user->forceFill([
            'join_restricted_until' => now()->addDays((int) $data['days']),
            'join_restriction_reason' => trim((string) ($data['reason'] ?? '')) ?: 'تكرار الغياب عن الأحداث',
        ])->save();

        UserNotification::send($user, 'join_restricted', 'قُيّد انضمامك للأحداث الجديدة لمدة '.$data['days'].' أيام بسبب تكرار الغياب. ستعود الإمكانية تلقائيًا بعد انتهاء المدة.', route('events.index'));
        AdminLog::record($request->user(), 'user.join_restricted', 'user', $user->id, $user->name, $data['days'].' يوم');

        return back()->with('success', 'قُيّد انضمام '.$user->name.' لمدة '.$data['days'].' أيام.');
    }

    public function unrestrict(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['join_restricted_until' => null, 'join_restriction_reason' => null])->save();
        UserNotification::send($user, 'join_restricted', 'رُفع تقييد الانضمام عن حسابك. يمكنك التسجيل في الأحداث من جديد.', route('events.index'));
        AdminLog::record($request->user(), 'user.join_unrestricted', 'user', $user->id, $user->name);

        return back()->with('success', 'رُفع التقييد عن '.$user->name.'.');
    }

    public function sweep(): RedirectResponse
    {
        Artisan::call('liqa:attendance-sweep');

        return back()->with('success', trim(Artisan::output()));
    }
}
