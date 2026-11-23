<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** طلب تواصل بين عضوين التقيا في حدث فعلي */
class ContactRequest extends Model
{
    public const PENDING = 'pending';

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    protected $fillable = ['from_user_id', 'to_user_id', 'event_id', 'status'];

    public function from(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** أحداث منتهية حضرها العضو فعليًا (أو نظّمها وحضرها أحد) */
    private static function metEventIds(User $user): array
    {
        $attended = EventAttendance::where('user_id', $user->id)->where('seconds', '>=', EventAttendance::THRESHOLD)->pluck('event_id');

        $hosted = Event::where('user_id', $user->id)->where('status', Event::PUBLISHED)
            ->whereHas('attendances', fn ($q) => $q->where('seconds', '>=', EventAttendance::THRESHOLD))
            ->pluck('id');

        return $attended->merge($hosted)->unique()->all();
    }

    /** آخر حدث التقى فيه العضوان فعليًا، أو null إن لم يلتقيا */
    public static function sharedEvent(User $a, User $b): ?Event
    {
        $ids = array_intersect(self::metEventIds($a), self::metEventIds($b));

        if (! $ids) {
            return null;
        }

        return Event::whereIn('id', $ids)->orderByDesc('starts_at')->get()->first(fn ($e) => $e->hasEnded());
    }

    /** الطلب القائم بين عضوين بأي اتجاه */
    public static function between(User $a, User $b): ?self
    {
        return self::where(fn ($q) => $q->where('from_user_id', $a->id)->where('to_user_id', $b->id))
            ->orWhere(fn ($q) => $q->where('from_user_id', $b->id)->where('to_user_id', $a->id))
            ->first();
    }
}
