<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventRequest;
use App\Models\User;

/**
 * الالتزام بالحضور: يُحسب من الأحداث المنتهية التي سُجّل فيها حضور فعلي (غرفة أو QR).
 * الغياب = طلب مقبول في حدث منتهٍ دون بلوغ حدّ الحضور. الأحداث التي لم يُسجَّل فيها حضور لأحد لا تُحتسب.
 */
class Reliability
{
    public const WINDOW_DAYS = 180;

    public const STRIKE_DAYS = 90;

    private static ?array $cache = null;

    public static function flush(): void
    {
        self::$cache = null;
    }

    /** [user_id => [eligible, attended, missed, recent_missed]] */
    public static function history(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $events = Event::where('status', Event::PUBLISHED)
            ->where('starts_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->where('starts_at', '<', now())
            ->get(['id', 'starts_at', 'duration_minutes'])
            ->filter(fn (Event $e) => $e->hasEnded())
            ->keyBy('id');

        if ($events->isEmpty()) {
            return self::$cache = [];
        }

        $present = EventAttendance::whereIn('event_id', $events->keys())
            ->where('seconds', '>=', EventAttendance::THRESHOLD)
            ->get(['event_id', 'user_id']);

        $trackedIds = $present->pluck('event_id')->unique()->flip();
        $presentKeys = $present->mapWithKeys(fn ($a) => [$a->event_id.':'.$a->user_id => true]);

        $cut = now()->subDays(self::STRIKE_DAYS);
        $out = [];

        EventRequest::whereIn('event_id', $trackedIds->keys())
            ->where('status', EventRequest::ACCEPTED)
            ->get(['event_id', 'user_id'])
            ->each(function ($r) use (&$out, $presentKeys, $events, $cut) {
                $row = &$out[$r->user_id];
                $row ??= ['eligible' => 0, 'attended' => 0, 'missed' => 0, 'recent_missed' => 0];
                $row['eligible']++;
                if (isset($presentKeys[$r->event_id.':'.$r->user_id])) {
                    $row['attended']++;
                } else {
                    $row['missed']++;
                    if ($events[$r->event_id]->starts_at->gte($cut)) {
                        $row['recent_missed']++;
                    }
                }
            });

        return self::$cache = $out;
    }

    public static function stats(User $user): array
    {
        $h = self::history()[$user->id] ?? ['eligible' => 0, 'attended' => 0, 'missed' => 0, 'recent_missed' => 0];
        $h['rate'] = $h['eligible'] > 0 ? round($h['attended'] / $h['eligible'] * 100) : null;

        return $h;
    }

    /** احتمال حضور عضو (تنعيم بيزي: من لا تاريخ له يُفترض ~75%) */
    public static function showProbability(int $userId): float
    {
        $h = self::history()[$userId] ?? ['eligible' => 0, 'attended' => 0];

        return ($h['attended'] + 3) / ($h['eligible'] + 4);
    }

    /** توقع الحضور لحدث: العدد المتوقع + الأعضاء الأعلى خطر غياب */
    public static function predict(Event $event): array
    {
        $users = $event->acceptedRequests()->with('user')->get()->pluck('user')->filter();

        $items = $users->map(function (User $u) {
            $h = self::history()[$u->id] ?? ['eligible' => 0, 'missed' => 0];

            return ['user' => $u, 'p' => self::showProbability($u->id), 'eligible' => $h['eligible'], 'missed' => $h['missed']];
        });

        return [
            'accepted' => $items->count(),
            'expected' => round($items->sum('p'), 1),
            'risky' => $items->filter(fn ($i) => $i['missed'] >= 1 && $i['p'] < 0.6)->sortBy('p')->values(),
        ];
    }

    /** مقاعد إضافية مقترحة لحدث ممتلئ له قائمة انتظار (بحد أقصى نصف السعة وعدد المنتظرين) */
    public static function extraSeats(Event $event): int
    {
        $waiting = $event->requests()->where('status', EventRequest::WAITLISTED)->count();
        if ($waiting < 1 || ! $event->isFull()) {
            return 0;
        }

        $p = self::predict($event);
        $emptyExpected = (int) floor($p['accepted'] - $p['expected']);

        return max(0, min($waiting, $emptyExpected, (int) floor($event->capacity / 2)));
    }
}
