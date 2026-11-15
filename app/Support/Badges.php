<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * الشارات: تُحسب تلقائيًا من نشاط العضو ولا تُخزَّن، فلا تحتاج صيانة.
 * "المنتهي" هنا يعني حدثًا منشورًا مضى موعد بدايته.
 */
class Badges
{
    /** إحصاءات العضو اللازمة للشارات */
    public static function stats(User $user): array
    {
        $ratings = $user->ratingsReceived();

        return [
            'hosted' => $user->hostedEvents()->where('status', Event::PUBLISHED)->where('starts_at', '<', now())->count(),
            'attended' => $user->eventRequests()->where('status', EventRequest::ACCEPTED)
                ->whereHas('event', fn ($q) => $q->where('status', Event::PUBLISHED)->where('starts_at', '<', now()))->count(),
            'ratings' => (clone $ratings)->count(),
            'avg' => (clone $ratings)->avg('stars'),
            'user_id' => $user->id,
            'complete' => (bool) ($user->avatar_path && $user->bio && $user->specialty),
        ];
    }

    /** الشارات من إحصاءات جاهزة (تُستخدم في الدليل بلا استعلامات إضافية) */
    public static function fromStats(array $s): array
    {
        $out = [];

        $host = self::tier($s['hosted'] ?? 0, [[15, 'منظّم رائد', 3], [5, 'منظّم نشط', 2], [1, 'منظّم لقاءات', 1]]);
        if ($host) {
            $out[] = ['key' => 'host', 'icon' => '🎤', 'label' => $host[0], 'tier' => $host[1], 'desc' => 'نشر '.$s['hosted'].' من اللقاءات التي أُقيمت.'];
        }

        $guest = self::tier($s['attended'] ?? 0, [[15, 'مشارك دائم', 3], [5, 'مشارك متفاعل', 2], [1, 'مشارك', 1]]);
        if ($guest) {
            $out[] = ['key' => 'guest', 'icon' => '🤝', 'label' => $guest[0], 'tier' => $guest[1], 'desc' => 'حضر '.$s['attended'].' من اللقاءات.'];
        }

        $avg = (float) ($s['avg'] ?? 0);
        $count = (int) ($s['ratings'] ?? 0);
        if ($count >= 3 && $avg >= 4.5) {
            $out[] = ['key' => 'rated', 'icon' => '⭐', 'label' => 'تقييم ممتاز', 'tier' => 3, 'desc' => 'متوسط تقييم '.round($avg, 1).' من '.$count.' تقييمات.'];
        } elseif ($count >= 3 && $avg >= 4.0) {
            $out[] = ['key' => 'rated', 'icon' => '⭐', 'label' => 'محبوب بين الأعضاء', 'tier' => 2, 'desc' => 'متوسط تقييم '.round($avg, 1).' من '.$count.' تقييمات.'];
        }

        if (! empty($s['complete'])) {
            $out[] = ['key' => 'complete', 'icon' => '✅', 'label' => 'ملف مكتمل', 'tier' => 1, 'desc' => 'أضاف صورة ونبذة وتخصصًا.'];
        }

        if (isset($s['user_id']) && $s['user_id'] <= self::founderCutoff()) {
            $out[] = ['key' => 'founder', 'icon' => '🏅', 'label' => 'عضو مؤسس', 'tier' => 3, 'desc' => 'من أوائل الأعضاء الذين انضموا إلى المنصة.'];
        }

        return $out;
    }

    public static function for(User $user): array
    {
        return self::fromStats(self::stats($user));
    }

    private static function tier(int $value, array $levels): ?array
    {
        foreach ($levels as [$min, $label, $tier]) {
            if ($value >= $min) {
                return [$label, $tier];
            }
        }

        return null;
    }

    /** معرّف آخر عضو مؤسس (أول 50 عضوًا) */
    private static function founderCutoff(): int
    {
        return (int) Cache::store('file')->remember('liqa.founder-cutoff', 3600, fn () => User::orderBy('id')->skip(49)->value('id') ?? PHP_INT_MAX);
    }
}
